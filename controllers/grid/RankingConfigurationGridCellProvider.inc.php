<?php

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class RankingConfigurationGridCellProvider extends GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $tab = $row->getData();
        $columnId = $column->getId();

        switch ($columnId) {
            case 'defaultTitle':
                return array('label' => $tab['label']);
            case 'customTitle':
                return array('label' => '');
            case 'customDescription':
                return array('label' => '');
            default:
                break;
        }

        return parent::getTemplateVarsFromRowColumn($row, $column);
    }
}
