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
                return $this->showSettings($request);
            case 'configurationGuide':
                return (new ConfigurationGuide($this->plugin))->execute($request);
            default:
                return $this->plugin->parentManage($args, $request);
        }
    }

    private function showSettings($request): JSONMessage
    {
        $templateManager = TemplateManager::getManager($request);
        $templateManager->assign('settingsApiUrl', $request->getDispatcher()->url(
            $request,
            Application::ROUTE_API,
            $request->getContext()->getPath(),
            "plugins/{$this->plugin->getName()}/settings"
        ));

        return new JSONMessage(true, $templateManager->fetch($this->plugin->getTemplateResource('settings.tpl')));
    }
}
