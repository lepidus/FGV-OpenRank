<?php

import('classes.template.TemplateManager');
import('lib.pkp.classes.core.JSONMessage');

class ConfigurationGuide
{
    public $plugin;

    public function __construct(&$plugin)
    {
        $this->plugin = &$plugin;
    }

    public function execute($request): JSONMessage
    {
        $templateManager = TemplateManager::getManager($request);
        $templateManager->assign(array_merge(
            $this->getGuideUrls($request),
            ['pluginUrl' => $request->getBaseUrl() . '/' . $this->plugin->getPluginPath()]
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
            'guideHomepageUrl' => $request->getDispatcher()->url(
                $request,
                ROUTE_PAGE,
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
            ROUTE_PAGE,
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
