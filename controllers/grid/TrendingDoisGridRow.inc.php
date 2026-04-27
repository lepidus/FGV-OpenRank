<?php

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.LinkAction');
import('lib.pkp.classes.linkAction.request.AjaxModal');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class TrendingDoisGridRow extends GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);
        $router = $request->getRouter();
        $rowId = $this->getId();
        if (!$rowId) {
            return;
        }

        $this->addAction(
            new LinkAction(
                'editDoi',
                new AjaxModal(
                    $router->url($request, null, null, 'editDoi', null, ['rowId' => $rowId]),
                    __('grid.action.edit'),
                    'modal_edit',
                    true
                ),
                __('grid.action.edit'),
                'edit'
            )
        );

        $this->addAction(
            new LinkAction(
                'deleteDoi',
                new RemoteActionConfirmationModal(
                    $request->getSession(),
                    __('plugins.generic.rankingPlugin.trendingDois.deleteConfirm'),
                    __('grid.action.delete'),
                    $router->url($request, null, null, 'deleteDoi', null, ['rowId' => $rowId]),
                    'modal_delete'
                ),
                __('grid.action.delete'),
                'delete'
            )
        );
    }
}
