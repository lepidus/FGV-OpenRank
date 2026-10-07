<?php

namespace APP\plugins\generic\fgvOpenRank\classes\tasks;

use APP\core\Application;
use APP\plugins\generic\fgvOpenRank\classes\services\RankingTabService;
use Exception;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\ScheduledTask;
use PKP\scheduledTask\ScheduledTaskHelper;

class RankingCacheUpdateTask extends ScheduledTask
{
    public function getName(): string
    {
        return __('plugins.generic.fgvOpenRank.scheduledTask.name');
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
        $plugin = PluginRegistry::getPlugin('generic', 'fgvopenrankplugin')
            ?? PluginRegistry::loadPlugin('generic', 'fgvOpenRank', $context->getId());

        if (!$plugin || !$plugin->getEnabled($context->getId())) {
            return;
        }

        $this->addExecutionLogEntry(
            __('plugins.generic.fgvOpenRank.scheduledTask.updateStart', ['contextName' => $context->getLocalizedName()]),
            ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
        );

        try {
            (new RankingTabService($plugin, $context, $request))->refreshAll();

            $this->addExecutionLogEntry(
                __('plugins.generic.fgvOpenRank.scheduledTask.updateComplete', ['contextName' => $context->getLocalizedName()]),
                ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
            );
        } catch (Exception $e) {
            $this->addExecutionLogEntry(
                __('plugins.generic.fgvOpenRank.scheduledTask.updateError', [
                    'contextName' => $context->getLocalizedName(),
                    'error' => $e->getMessage(),
                ]),
                ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_ERROR
            );
        }
    }
}
