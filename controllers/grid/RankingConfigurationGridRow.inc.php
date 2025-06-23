<?php

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class RankingConfigurationGridRow extends GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);
        $rowData = $this->getData();

        $tabId = $rowData['id'];
        $router = $request->getRouter();

        $this->addAction(
            new LinkAction(
                'editTab',
                new AjaxModal(
                    $router->url(
                        $request,
                        null,
                        null,
                        'editTab',
                        null,
                        array('tabId' => $tabId)
                    ),
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
