<?php

namespace APP\plugins\generic\rankingPlugin\classes\tasks;

use APP\core\Application;
use APP\plugins\generic\rankingPlugin\classes\services\RankingTabService;
use Exception;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\ScheduledTask;
use PKP\scheduledTask\ScheduledTaskHelper;

class RankingCacheUpdateTask extends ScheduledTask
{
    public function getName(): string
    {
        return __('plugins.generic.rankingPlugin.scheduledTask.name');
    }

    protected function executeActions(): bool
    {
        $request = Application::get()->getRequest();
        $request->setDispatcher(Application::get()->getDispatcher());

        $contexts = app()->get('context')->getMany(['isEnabled' => true]);
        foreach ($contexts as $context) {
            $this->updateContextCaches($context, $request);
        }

        return true;
    }

    private function updateContextCaches($context, $request): void
    {
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin')
            ?? PluginRegistry::loadPlugin('generic', 'rankingPlugin', $context->getId());

        if (!$plugin || !$plugin->getEnabled($context->getId())) {
            return;
        }

        $this->addExecutionLogEntry(
            __('plugins.generic.rankingPlugin.scheduledTask.updateStart', ['contextName' => $context->getLocalizedName()]),
            ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
        );

        try {
            (new RankingTabService($plugin, $context, $request))->refreshAll();

            $this->addExecutionLogEntry(
                __('plugins.generic.rankingPlugin.scheduledTask.updateComplete', ['contextName' => $context->getLocalizedName()]),
                ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
            );
        } catch (Exception $e) {
            $this->addExecutionLogEntry(
                __('plugins.generic.rankingPlugin.scheduledTask.updateError', [
                    'contextName' => $context->getLocalizedName(),
                    'error' => $e->getMessage(),
                ]),
                ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_ERROR
            );
        }
    }
}
