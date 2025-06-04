<?php

import('lib.pkp.classes.controllers.grid.GridCategoryRow');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class RankingConfigurationGridRow extends GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);
        $rowId = $this->getData()['id'];
        $dispatcher = $request->getDispatcher();
        $router = $request->getRouter();

        $this->addAction(
            new LinkAction(
                'editTab',
                new AjaxModal(
                    $router->url($request, null, null, 'editTab', null, array('tabId' => $rowId)),
                    __('grid.action.edit'),
                    'modal_edit',
                    true
                ),
                __('grid.action.edit'),
                'edit'
            )
        );
    }
}
