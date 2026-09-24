<?php

namespace APP\plugins\generic\rankingPlugin\classes;

use APP\core\Application;
use APP\plugins\generic\rankingPlugin\classes\api\v1\RankingPluginController;
use APP\plugins\generic\rankingPlugin\classes\api\v1\RankingPluginSettingsController;
use APP\template\TemplateManager;
use PKP\core\APIRouter;
use PKP\core\PKPBaseController;
use PKP\facades\Locale;
use PKP\handler\APIHandler;
use PKP\plugins\Hook;

class HookCallback
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function setupApiControllers(string $hookName, array $params): bool
    {
        $request = $params[0];
        $router = $request->getRouter();

        if (!($router instanceof APIRouter)) {
            return Hook::CONTINUE;
        }

        $controller = $this->getApiController($request->getRequestPath());
        if ($controller === null) {
            return Hook::CONTINUE;
        }

        $handler = new APIHandler($controller);
        $router->setHandler($handler);
        $handler->runRoutes();
        exit;
    }

    private function getApiController(string $requestPath): ?PKPBaseController
    {
        $settingsController = new RankingPluginSettingsController($this->plugin);
        if ($this->matchesHandlerPath($requestPath, $settingsController->getHandlerPath())) {
            return $settingsController;
        }

        $publicController = new RankingPluginController($this->plugin);
        if ($this->matchesHandlerPath($requestPath, $publicController->getHandlerPath())) {
            return $publicController;
        }

        return null;
    }

    private function matchesHandlerPath(string $requestPath, string $handlerPath): bool
    {
        return (bool) preg_match('#/api/v1/' . preg_quote($handlerPath, '#') . '(/|$)#', $requestPath);
    }

    public function handleMetricsData($hookName, $args)
    {
        $template = $args[1];

        if ($template !== 'frontend/pages/indexJournal.tpl') {
            return false;
        }

        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextId = $context->getId();

        $rankingPluginApiBaseUrl = $request->getDispatcher()->url(
            $request,
            Application::ROUTE_API,
            $context->getPath(),
            'rankingPlugin'
        );

        $locale = Locale::getLocale();
        $primaryLocale = $context->getPrimaryLocale();
        $rankingTabs = new RankingTabs($this->plugin, $contextId);
        $tabs = $rankingTabs->getOrderedEnabled();

        $customTitles = [];
        $customDescriptions = [];
        $highlightCustomContent = [];
        $tabSpecificSettings = [];

        foreach ($tabs as $tabId) {
            $customTitles[$tabId] = $rankingTabs->getLocalizedSetting("customTitle_{$tabId}", $locale, $primaryLocale);
            $customDescriptions[$tabId] = $rankingTabs->getLocalizedSetting("customDescription_{$tabId}", $locale, $primaryLocale);

            if ($tabId === RankingTabs::HIGHLIGHT) {
                $highlightCustomContent[$tabId] = $rankingTabs->getLocalizedSetting("highlightContent_{$tabId}", $locale, $primaryLocale);
            }

            $tabSpecificSettings[$tabId] = [
                'itemsPerTab' => $rankingTabs->getItemsPerTab($tabId),
                'itemsPerPage' => $rankingTabs->getItemsPerPage($tabId),
            ];
        }

        $templateMgr->assign([
            'customTitles' => $customTitles,
            'customDescriptions' => $customDescriptions,
            'highlightCustomContent' => $highlightCustomContent,
            'orderedTabs' => $tabs,
        ]);

        $rankingPluginJavaScriptVariables = $this->getDisplayPositionSettings($contextId) + [
            'currentLocale' => $locale,
            'primaryLocale' => $primaryLocale,
            'publishedDateLocaleMessage' => __('plugins.generic.rankingPlugin.tabs.content.publishedDate'),
            'rankingTemplate' => $templateMgr->fetch($this->plugin->getTemplateResource('ranking.tpl')),
            'rankingPluginApiBaseUrl' => $rankingPluginApiBaseUrl,
            'itemsPerTab' => RankingTabs::DEFAULT_ITEMS,
            'itemsPerPage' => RankingTabs::DEFAULT_ITEMS,
            'tabSettings' => $tabSpecificSettings,
            'previousPageLabel' => '<',
            'nextPageLabel' => '>',
            'mostRecentFailedMessage' => __('plugins.generic.rankingPlugin.tabs.mostRecentFailed'),
            'mostReadFailedMessage' => __('plugins.generic.rankingPlugin.tabs.mostReadFailed'),
            'mostCitedFailedMessage' => __('plugins.generic.rankingPlugin.tabs.mostCitedFailed'),
            'trendingFailedMessage' => __('plugins.generic.rankingPlugin.tabs.trendingFailed'),
            'noPublicationsFoundMessage' => __('plugins.generic.rankingPlugin.NoPublicationsFound'),
        ];

        $this->loadResources($templateMgr, $request, $rankingPluginJavaScriptVariables);

        return false;
    }

    public function insertRankingPlaceholder($hookName, $args)
    {
        $contextId = $this->getContextId();

        if ($contextId === null) {
            return false;
        }

        $settings = $this->getDisplayPositionSettings($contextId);

        if (!RankingDisplayPosition::needsPlaceholder($settings['displayPosition'])) {
            return false;
        }

        $args[2] .= RankingDisplayPosition::PLACEHOLDER;

        return false;
    }

    public function getDisplayPositionSettings($contextId): array
    {
        return [
            'displayPosition' => RankingDisplayPosition::normalize(
                $this->plugin->getSetting($contextId, RankingDisplayPosition::SETTING_NAME)
            ),
            'displayPositionSection' => RankingDisplayPosition::normalizeSection(
                $this->plugin->getSetting($contextId, RankingDisplayPosition::SECTION_SETTING_NAME)
            ),
        ];
    }

    protected function getContextId()
    {
        $context = Application::get()->getRequest()->getContext();

        return $context === null ? null : $context->getId();
    }

    private function loadResources($templateMgr, $request, $rankingPluginJavaScriptVariables)
    {
        $pluginUrl = $request->getBaseUrl() . '/' . $this->plugin->getPluginPath();
        $assetVersion = $this->plugin->getAssetVersion();
        $options = ['priority' => TemplateManager::STYLE_SEQUENCE_LAST, 'contexts' => 'frontend'];

        $templateMgr->addJavaScript(
            'AppData',
            'app = ' . json_encode($rankingPluginJavaScriptVariables) . ';',
            ['inline' => true, 'contexts' => 'frontend']
        );
        $templateMgr->addJavaScript('momentJs', "{$pluginUrl}/js/lib/momentjs/moment.min.js", $options);
        $templateMgr->addJavaScript('rankingPluginScript', "{$pluginUrl}/js/insertRankingTemplate.js?v={$assetVersion}", $options);
        $templateMgr->addStyleSheet('rankingPluginStyles', "{$pluginUrl}/styles/ranking.css?v={$assetVersion}", $options);
        $templateMgr->addStyleSheet('rankingPluginPaginationStyles', "{$pluginUrl}/styles/pagination.css?v={$assetVersion}", $options);
    }
}
