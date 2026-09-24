<?php

namespace APP\plugins\generic\rankingPlugin;

use APP\core\Application;
use APP\plugins\generic\rankingPlugin\classes\HookCallback;
use APP\plugins\generic\rankingPlugin\classes\migrations\LegacySettingsMigration;
use APP\plugins\generic\rankingPlugin\classes\settings\Actions;
use APP\plugins\generic\rankingPlugin\classes\settings\Manage;
use APP\plugins\generic\rankingPlugin\classes\tasks\RankingCacheUpdateTask;
use APP\template\TemplateManager;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\interfaces\HasTaskScheduler;
use PKP\scheduledTask\PKPScheduler;

class RankingPlugin extends GenericPlugin implements HasTaskScheduler
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if (Application::isUnderMaintenance()) {
            return $success;
        }

        if ($success && $this->getEnabled($mainContextId)) {
            $hookCallback = new HookCallback($this);
            Hook::add('Dispatcher::dispatch', [$hookCallback, 'setupApiControllers']);
            Hook::add('TemplateManager::display', [$hookCallback, 'handleMetricsData']);
            Hook::add('Templates::Index::journal', [$hookCallback, 'insertRankingPlaceholder']);
            $this->addBackendAssets();
        }

        return $success;
    }

    public function registerSchedules(PKPScheduler $scheduler): void
    {
        $scheduler
            ->addSchedule(new RankingCacheUpdateTask())
            ->daily()
            ->name(RankingCacheUpdateTask::class)
            ->withoutOverlapping();
    }

    public function getInstallMigration()
    {
        return new LegacySettingsMigration();
    }

    public function getDisplayName()
    {
        return __('plugins.generic.rankingPlugin.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.rankingPlugin.description');
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

    private function addBackendAssets(): void
    {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $buildUrl = "{$request->getBaseUrl()}/{$this->getPluginPath()}/public/build";

        $templateMgr->addJavaScript(
            'rankingPluginSettings',
            "{$buildUrl}/build.iife.js?v={$this->getAssetVersion()}",
            [
                'inline' => false,
                'contexts' => ['backend'],
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
            ]
        );
        $templateMgr->addStyleSheet(
            'rankingPluginSettings',
            "{$buildUrl}/build.css?v={$this->getAssetVersion()}",
            ['contexts' => ['backend']]
        );
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\rankingPlugin\RankingPlugin', '\RankingPlugin');
}
