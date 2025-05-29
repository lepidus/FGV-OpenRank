<?php

import('lib.pkp.classes.controllers.grid.GridCategoryRow');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class RankingConfigurationGridRow extends GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        $rowId = $this->getId();
        $dispatcher = $request->getDispatcher();
        $router = $request->getRouter();

        $this->addAction(
            new LinkAction(
                'editTab',
                new AjaxModal(
                    $router->url($request, null, null, 'editTab', null, array('tab' => $rowId)),
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
