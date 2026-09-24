<?php

namespace APP\plugins\generic\rankingPlugin\classes\api\v1;

use APP\core\Application;
use APP\plugins\generic\rankingPlugin\classes\RankingTabs;
use APP\plugins\generic\rankingPlugin\classes\cache\TrendingSubmissions;
use APP\plugins\generic\rankingPlugin\classes\components\forms\DisplayPositionForm;
use APP\plugins\generic\rankingPlugin\classes\components\forms\TabSettingsForm;
use APP\plugins\generic\rankingPlugin\classes\components\forms\TrendingDoiForm;
use APP\plugins\generic\rankingPlugin\classes\settings\DisplayPositionSettings;
use APP\plugins\generic\rankingPlugin\classes\settings\TabSettings;
use APP\plugins\generic\rankingPlugin\classes\settings\TrendingDois;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\core\PKPBaseController;
use PKP\core\PKPRequest;
use PKP\facades\Locale;
use PKP\security\Role;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\authorization\UserRolesRequiredPolicy;

class RankingPluginSettingsController extends PKPBaseController
{
    public function __construct(
        private $plugin
    ) {
    }

    public function getHandlerPath(): string
    {
        return "plugins/{$this->plugin->getName()}/settings";
    }

    public function getRouteGroupMiddleware(): array
    {
        return [
            'has.user',
            'has.context',
            self::roleAuthorizer([Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER]),
        ];
    }

    public function getGroupRoutes(): void
    {
        Route::get('', $this->get(...))
            ->name('plugin.rankingplugin.settings.get');
        Route::put('', $this->edit(...))
            ->name('plugin.rankingplugin.settings.edit');
        Route::put('tabs', $this->editTabs(...))
            ->name('plugin.rankingplugin.settings.tabs.edit');
        Route::put('tabs/{tabId}', $this->editTab(...))
            ->name('plugin.rankingplugin.settings.tab.edit')
            ->whereIn('tabId', RankingTabs::getAll());
        Route::post('trendingDois', $this->addTrendingDoi(...))
            ->name('plugin.rankingplugin.settings.trendingDois.add');
        Route::put('trendingDois/order', $this->orderTrendingDois(...))
            ->name('plugin.rankingplugin.settings.trendingDois.order');
        Route::put('trendingDois/{doiId}', $this->editTrendingDoi(...))
            ->name('plugin.rankingplugin.settings.trendingDois.edit')
            ->whereAlphaNumeric('doiId');
        Route::delete('trendingDois/{doiId}', $this->deleteTrendingDoi(...))
            ->name('plugin.rankingplugin.settings.trendingDois.delete')
            ->whereAlphaNumeric('doiId');
    }

    public function authorize(PKPRequest $request, array &$args, array $roleAssignments): bool
    {
        $this->addPolicy(new UserRolesRequiredPolicy($request), true);
        $this->addPolicy(new ContextRequiredPolicy($request));

        $rolePolicy = new PolicySet(PolicySet::COMBINING_PERMIT_OVERRIDES);
        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function get(IlluminateRequest $illuminateRequest): JsonResponse
    {
        return response()->json($this->getSettingsState(), Response::HTTP_OK);
    }

    public function edit(IlluminateRequest $illuminateRequest): JsonResponse
    {
        (new DisplayPositionSettings($this->plugin, $this->getContextId()))->save($illuminateRequest->all());

        return response()->json($this->getSettingsState(), Response::HTTP_OK);
    }

    public function editTabs(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $tabs = $illuminateRequest->input('tabs');
        if (!is_array($tabs)) {
            return response()->json(['error' => __('api.400.paramNotSupported', ['param' => 'tabs'])], Response::HTTP_BAD_REQUEST);
        }

        (new RankingTabs($this->plugin, $this->getContextId()))->save($tabs);

        return response()->json($this->getSettingsState(), Response::HTTP_OK);
    }

    public function editTab(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $tabId = $illuminateRequest->route('tabId');
        $tabSettings = new TabSettings($this->plugin, $this->getContextId(), $tabId);
        $input = $illuminateRequest->all();

        $errors = $tabSettings->validate($input);
        if (!empty($errors)) {
            return response()->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tabSettings->save($input);

        return response()->json($this->getSettingsState(), Response::HTTP_OK);
    }

    public function addTrendingDoi(IlluminateRequest $illuminateRequest): JsonResponse
    {
        return $this->saveTrendingDoi($illuminateRequest, null);
    }

    public function editTrendingDoi(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $doiId = $illuminateRequest->route('doiId');
        if (!$this->trendingDoiExists($doiId)) {
            return response()->json(['error' => __('api.404.resourceNotFound')], Response::HTTP_NOT_FOUND);
        }

        return $this->saveTrendingDoi($illuminateRequest, $doiId);
    }

    public function deleteTrendingDoi(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $doiId = $illuminateRequest->route('doiId');
        if (!$this->trendingDoiExists($doiId)) {
            return response()->json(['error' => __('api.404.resourceNotFound')], Response::HTTP_NOT_FOUND);
        }

        $this->getTrendingDois()->remove($doiId);
        $this->refreshTrendingCache();

        return response()->json($this->getSettingsState(), Response::HTTP_OK);
    }

    public function orderTrendingDois(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $this->getTrendingDois()->reorder((array) $illuminateRequest->input('ids', []));
        $this->refreshTrendingCache();

        return response()->json($this->getSettingsState(), Response::HTTP_OK);
    }

    private function saveTrendingDoi(IlluminateRequest $illuminateRequest, ?string $doiId): JsonResponse
    {
        $doi = trim((string) $illuminateRequest->input('doi'));
        $trendingDois = $this->getTrendingDois();

        $error = $trendingDois->validate($doi);
        if ($error !== null) {
            return response()->json(['doi' => [$error]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $trendingDois->save($doi, $doiId);
        $this->refreshTrendingCache();

        return response()->json($this->getSettingsState(), Response::HTTP_OK);
    }

    private function getSettingsState(): array
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $contextId = $context->getId();
        $locale = Locale::getLocale();
        $rankingTabs = new RankingTabs($this->plugin, $contextId);
        $locales = $this->getFormLocales($context);

        $tabs = [];
        $tabForms = [];
        foreach ($rankingTabs->getOrdered() as $tabId) {
            $tabs[] = [
                'id' => $tabId,
                'label' => __("plugins.generic.rankingPlugin.tabs.{$tabId}.defaultTitle"),
                'customTitle' => $this->getSettingInLocale("customTitle_{$tabId}", $locale),
                'customDescription' => $this->getSettingInLocale("customDescription_{$tabId}", $locale),
                'enabled' => $rankingTabs->isEnabled($tabId),
            ];

            $tabValues = (new TabSettings($this->plugin, $contextId, $tabId))->getValues();
            $tabForms[$tabId] = (new TabSettingsForm($this->getApiUrl("tabs/{$tabId}"), $locales, $tabId, $tabValues))->getConfig();
        }

        return [
            'tabs' => $tabs,
            'tabForms' => $tabForms,
            'displayPositionForm' => (new DisplayPositionForm($this->getApiUrl(''), $this->plugin, $contextId))->getConfig(),
            'trendingDois' => $this->getTrendingDois()->getItems(),
            'trendingDoisApiUrl' => $this->getApiUrl('trendingDois'),
            'trendingDoiForm' => (new TrendingDoiForm($this->getApiUrl('trendingDois')))->getConfig(),
            'hasAltmetricsApiKey' => (new TabSettings($this->plugin, $contextId, RankingTabs::TRENDING))->hasApiKey(),
        ];
    }

    private function getSettingInLocale(string $settingName, string $locale): ?string
    {
        $value = $this->plugin->getSetting($this->getContextId(), $settingName);
        return is_array($value) ? ($value[$locale] ?? null) : null;
    }

    private function getFormLocales($context): array
    {
        $localeNames = $context->getSupportedFormLocaleNames();
        return array_map(
            fn (string $locale, string $name) => ['key' => $locale, 'label' => $name],
            array_keys($localeNames),
            $localeNames
        );
    }

    private function getApiUrl(string $path): string
    {
        $request = $this->getRequest();
        return $request->getDispatcher()->url(
            $request,
            Application::ROUTE_API,
            $request->getContext()->getPath(),
            rtrim($this->getHandlerPath() . '/' . $path, '/')
        );
    }

    private function trendingDoiExists(string $doiId): bool
    {
        return array_key_exists($doiId, $this->getTrendingDois()->getStored());
    }

    private function getTrendingDois(): TrendingDois
    {
        return new TrendingDois($this->plugin, $this->getContextId());
    }

    private function refreshTrendingCache(): void
    {
        $context = $this->getRequest()->getContext();
        $limit = (new RankingTabs($this->plugin, $context->getId()))->getItemsPerTab(RankingTabs::TRENDING);

        try {
            (new TrendingSubmissions($this->plugin))->refreshCache($context->getId(), $context->getPath(), $limit);
        } catch (Exception $error) {
            error_log($error->getMessage());
        }
    }

    private function getContextId(): int
    {
        return $this->getRequest()->getContext()->getId();
    }
}
