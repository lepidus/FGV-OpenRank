<?php

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.fgvOpenRank.classes.HookCallback');
import('plugins.generic.fgvOpenRank.classes.settings.Manage');
import('plugins.generic.fgvOpenRank.classes.settings.Actions');
import('plugins.generic.fgvOpenRank.classes.migrations.FgvOpenRankRenameMigration');

define('ONE_DAY_SECONDS', 60 * 60 * 24);

class FgvOpenRankPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);
        if ($success && !$this->getEnabled() && FgvOpenRankRenameMigration::hasLegacySettings()) {
            // Uploaded while rankingPlugin was still installed, the stand-in plugin
            // ran instead and the install migration was skipped.
            (new FgvOpenRankRenameMigration())->up();
        }
        if ($success) {
            // Acron rebuilds its crontab in whatever request runs it, often one where the plugin is
            // not enabled (the installer, another journal); the task itself skips disabled journals.
            HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'parseCrontab'));
        }
        if ($success && $this->getEnabled()) {
            $hookCallback = new HookCallback($this);
            HookRegistry::register('Dispatcher::dispatch', array($hookCallback, 'setupFgvOpenRankAPIHandler'));
            HookRegistry::register('TemplateManager::display', [$hookCallback, 'handleMetricsData']);
            HookRegistry::register('Templates::Index::journal', [$hookCallback, 'insertRankingPlaceholder']);
            HookRegistry::register('Schema::get::submission', array($hookCallback, 'addScoreFieldToSubmissionSchema'));
            HookRegistry::register('LoadComponentHandler', array($hookCallback, 'setupRankingConfigurationGridHandler'));
        }
        return $success;
    }

    public function getInstallMigration()
    {
        return new FgvOpenRankRenameMigration();
    }

    public function getDisplayName()
    {
        return __('plugins.generic.fgvOpenRank.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.fgvOpenRank.description');
    }

    public function getAssetVersion(): string
    {
        $pluginVersion = $this->getCurrentVersion();
        if ($pluginVersion) {
            return $pluginVersion->getVersionString();
        }

        return Application::get()->getCurrentVersion()->getVersionString();
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

    /**
     * Fallback for verbs Manage does not handle. Manage has no way to reach
     * GenericPlugin::manage() on its own, and calling manage() again would
     * route straight back into it.
     */
    public function parentManage($args, $request)
    {
        return parent::manage($args, $request);
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
