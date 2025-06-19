<?php

import('lib.pkp.classes.controllers.grid.GridCellProvider');

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
}
