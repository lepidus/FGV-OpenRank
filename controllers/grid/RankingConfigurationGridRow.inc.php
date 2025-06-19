<?php

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.AjaxModal');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class RankingConfigurationGridRow extends GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);
        $rowData = $this->getData();
        $rowId = $this->getId();

        $tabId = $rowData['id'];

        $router = $request->getRouter();

        $this->addAction(
            new LinkAction(
                'editTab',
                new AjaxModal(
                    $router->url($request, null, null, 'editTab', null, array('tabId' => $tabId)),
                    __('grid.action.edit'),
                    'modal_edit',
                    true
                ),
                __('grid.action.edit'),
                'edit'
            )
        );

        $toggleAction = $rowData['enabled'] ? 'disable' : 'enable';
        $toggleLabel = $rowData['enabled'] ?
            __('plugins.generic.rankingPlugin.configuration.disableTab') :
            __('plugins.generic.rankingPlugin.configuration.enableTab');
        $toggleIcon = $rowData['enabled'] ? 'disable' : 'enable';

        $this->addAction(
            new LinkAction(
                'toggleTab',
                new RemoteActionConfirmationModal(
                    $request->getSession(),
                    $toggleLabel . '?',
                    __('common.confirm'),
                    $router->url($request, null, null, 'toggleTab', null, array('tabId' => $tabId)),
                    'modal_confirm'
                ),
                $toggleLabel,
                $toggleIcon
            )
        );
    }
}
