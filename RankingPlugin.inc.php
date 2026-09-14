<?php

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.rankingPlugin.classes.HookCallback');
import('plugins.generic.rankingPlugin.classes.settings.Manage');
import('plugins.generic.rankingPlugin.classes.settings.Actions');

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
            HookRegistry::register('Templates::Index::journal', [$hookCallback, 'insertRankingPlaceholder']);
            HookRegistry::register('Schema::get::submission', array($hookCallback, 'addScoreFieldToSubmissionSchema'));
            HookRegistry::register('LoadComponentHandler', array($hookCallback, 'setupRankingConfigurationGridHandler'));
            HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'parseCrontab'));
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

    public function getActions($request, $actionArgs)
    {
        $actions = new Actions($this);
        return $actions->execute($request, $actionArgs, parent::getActions($request, $actionArgs));
    }

    public function manage($args, $request)
    {
        $manage = new Manage($this);
        return $manage->execute($args, $request);
    }

    public function getCanEnable()
    {
        $request = Application::get()->getRequest();
        return $request->getContext() !== null;
    }

    public function getCanDisable()
    {
        $request = Application::get()->getRequest();
        return $request->getContext() !== null;
    }

    public function parseCrontab($hookName, $args)
    {
        $taskFilesPath = &$args[0];
        $taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
        return false;
    }
}
