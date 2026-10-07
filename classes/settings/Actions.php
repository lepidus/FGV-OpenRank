<?php

namespace APP\plugins\generic\fgvOpenRank\classes\settings;

use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

class Actions
{
    public function __construct(
        private $plugin
    ) {
    }

    public function execute($request, $actionArgs, $parentActions)
    {
        if (!$this->plugin->getEnabled()) {
            return $parentActions;
        }

        return array_merge(
            [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $this->manageUrl($request, 'settings'),
                        $this->plugin->getDisplayName()
                    ),
                    __('manager.plugins.settings')
                ),
                new LinkAction(
                    'configurationGuide',
                    new AjaxModal(
                        $this->manageUrl($request, 'configurationGuide'),
                        __('plugins.generic.fgvOpenRank.configurationGuide.title')
                    ),
                    __('plugins.generic.fgvOpenRank.configurationGuide.action')
                ),
            ],
            $parentActions
        );
    }

    private function manageUrl($request, $verb)
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
