<?php

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.rankingPlugin.classes.HookCallback');

define('ONE_DAY_SECONDS', 60 * 60 * 24);

class RankingPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);
        if ($success && $this->getEnabled()) {
            $hookCallback = new HookCallback($this);
            HookRegistry::register('Dispatcher::dispatch', array($hookCallback, 'setupRankingPluginAPIHandler'));
            HookRegistry::register('TemplateManager::display', [$hookCallback, 'handleMetricsData']);
            HookRegistry::register('Schema::get::submission', array($hookCallback, 'addScoreFieldToSubmissionSchema'));
        }
        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.rankingPlugin.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.rankingPlugin.description');
    }
}
