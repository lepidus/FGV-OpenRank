<?php

namespace APP\plugins\generic\rankingPlugin\classes\settings;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;

class Manage
{
    public function __construct(
        private $plugin
    ) {
    }

    public function execute($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                return $this->showSettings($request, 'settings.tpl');
            case 'tabSettings':
                return $this->showSettings($request, 'tabSettings.tpl', ['tabId' => (string) $request->getUserVar('tabId')]);
            case 'trendingDoi':
                return $this->showSettings($request, 'trendingDoi.tpl', ['doiId' => (string) $request->getUserVar('doiId')]);
            case 'configurationGuide':
                return (new ConfigurationGuide($this->plugin))->execute($request);
            default:
                return $this->plugin->parentManage($args, $request);
        }
    }

    private function showSettings($request, string $template, array $variables = []): JSONMessage
    {
        $templateManager = TemplateManager::getManager($request);
        $templateManager->assign([
            ...$variables,
            'settingsApiUrl' => $request->getDispatcher()->url(
                $request,
                Application::ROUTE_API,
                $request->getContext()->getPath(),
                "plugins/{$this->plugin->getName()}/settings"
            ),
            'tabSettingsUrl' => $this->manageUrl($request, 'tabSettings'),
            'trendingDoiUrl' => $this->manageUrl($request, 'trendingDoi'),
        ]);

        return new JSONMessage(true, $templateManager->fetch($this->plugin->getTemplateResource($template)));
    }

    private function manageUrl($request, string $verb): string
    {
        return $request->getRouter()->url(
            $request,
            null,
            null,
            'manage',
            null,
            ['verb' => $verb, 'plugin' => $this->plugin->getName(), 'category' => 'generic']
        );
    }
}
