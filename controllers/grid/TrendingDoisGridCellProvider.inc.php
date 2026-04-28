<?php

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class TrendingDoisGridCellProvider extends GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $data = $row->getData();
        $columnId = $column->getId();

        if ($columnId === 'doi') {
            return ['label' => $data['doi'] ?? ''];
        }

        return parent::getTemplateVarsFromRowColumn($row, $column);
    }
}
