<?php

import('lib.pkp.classes.plugins.GenericPlugin');

/**
 * Stands in for FgvOpenRankPlugin while plugins/generic/rankingPlugin is still
 * installed, and tells the administrator to delete it.
 */
class FgvOpenRankPendingRemovalPlugin extends GenericPlugin
{
    public function getDisplayName()
    {
        return __('plugins.generic.fgvOpenRank.pendingRemoval.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.fgvOpenRank.pendingRemoval.description');
    }

    public function getCanEnable()
    {
        return false;
    }

    public function getCanDisable()
    {
        return false;
    }
}
