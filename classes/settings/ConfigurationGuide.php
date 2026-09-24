<?php

namespace APP\plugins\generic\rankingPlugin\classes\settings;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;

class ConfigurationGuide
{
    public function __construct(
        private $plugin
    ) {
    }

    public function execute($request): JSONMessage
    {
        $templateManager = TemplateManager::getManager($request);
        $templateManager->assign(array_merge(
            $this->getGuideUrls($request),
            [
                'pluginUrl' => $request->getBaseUrl() . '/' . $this->plugin->getPluginPath(),
                'assetVersion' => $this->plugin->getAssetVersion(),
            ]
        ));

        return new JSONMessage(
            true,
            $templateManager->fetch(
                $this->plugin->getTemplateResource('admin/configurationGuide.tpl')
            )
        );
    }

    private function getGuideUrls($request): array
    {
        $contextPath = $request->getContext()->getPath();

        return [
            'guideInstalledPluginsUrl' => $this->settingsUrl($request, $contextPath, 'website', 'plugins/installedPlugins'),
            'guideAdditionalContentUrl' => $this->settingsUrl($request, $contextPath, 'website', 'appearance/advanced'),
            'guideMastheadUrl' => $this->settingsUrl($request, $contextPath, 'context', 'masthead'),
            'guideDoiSetupUrl' => $this->settingsUrl($request, $contextPath, 'distribution', 'dois/doisSetup'),
            'guideDoiManagementUrl' => $request->getDispatcher()->url(
                $request,
                Application::ROUTE_PAGE,
                $contextPath,
                'dois',
                null,
                null,
                null,
                null,
                true
            ),
            'guideHomepageUrl' => $request->getDispatcher()->url(
                $request,
                Application::ROUTE_PAGE,
                $contextPath,
                'index',
                null,
                null,
                null,
                null,
                true
            ),
        ];
    }

    private function settingsUrl($request, $contextPath, $page, $anchor): string
    {
        return $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            $contextPath,
            'management',
            'settings',
            [$page],
            null,
            $anchor,
            true
        );
    }
}
