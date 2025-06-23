<?php

import('lib.pkp.classes.controllers.grid.GridCellProvider');
import('lib.pkp.classes.linkAction.LinkAction');
import('lib.pkp.classes.linkAction.request.AjaxAction');

class RankingConfigurationGridCellProvider extends GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $tab = $row->getData();
        $columnId = $column->getId();

        switch ($columnId) {
            case 'enabled':
                return array(
                    'selected' => $tab['enabled'],
                    'disabled' => false
                );
            case 'defaultTitle':
                return array('label' => $tab['label']);
            case 'customTitle':
                return array('label' => $tab['customTitle']);
            case 'customDescription':
                return array('label' => $tab['customDescription']);
            default:
                break;
        }

        return parent::getTemplateVarsFromRowColumn($row, $column);
    }

    public function getCellActions(
        $request,
        $row,
        $column,
        $position = GRID_ACTION_POSITION_DEFAULT
    ) {
        $tab = $row->getData();
        $router = $request->getRouter();
        $actions = array();
        $actionArgs = array('rowId' => $row->getId());

        $action = null;
        $actionRequest = null;

        switch ($column->getId()) {
            case 'enabled':
                $action = 'setTabEnabled-' . $row->getId();
                $actionArgs['value'] = !$tab['enabled'];
                $actionRequest = new AjaxAction(
                    $router->url(
                        $request,
                        null,
                        null,
                        'saveTabSetting',
                        null,
                        $actionArgs
                    )
                );
                break;
        }

        if ($action && $actionRequest) {
            $linkAction = new LinkAction($action, $actionRequest, null, null);
            $actions = array($linkAction);
        }

        return $actions;
    }
}
