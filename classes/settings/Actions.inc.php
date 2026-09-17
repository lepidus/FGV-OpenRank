<?php

class Actions
{
    public $plugin;

    public function __construct(&$plugin)
    {
        $this->plugin = &$plugin;
    }

    public function execute($request, $actionArgs, $parentActions)
    {
        $router = $request->getRouter();
        import('lib.pkp.classes.linkAction.request.AjaxModal');
        return array_merge(
            $this->plugin->getEnabled() ? array(
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $this->manageUrl($request, $router, 'settings'),
                        $this->plugin->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
                new LinkAction(
                    'configurationGuide',
                    new AjaxModal(
                        $this->manageUrl($request, $router, 'configurationGuide'),
                        __('plugins.generic.rankingPlugin.configurationGuide.title')
                    ),
                    __('plugins.generic.rankingPlugin.configurationGuide.action'),
                    null
                ),
            ) : array(),
            $parentActions
        );
    }

    private function manageUrl($request, $router, $verb)
    {
        return $router->url(
            $request,
            null,
            null,
            'manage',
            null,
            array('verb' => $verb, 'plugin' => $this->plugin->getName(), 'category' => 'generic')
        );
    }
}
