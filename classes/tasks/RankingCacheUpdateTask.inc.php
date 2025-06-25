<?php

import('lib.pkp.classes.scheduledTask.ScheduledTask');
import('plugins.generic.rankingPlugin.classes.cache.MostRecent');
import('plugins.generic.rankingPlugin.classes.cache.MostRead');
import('plugins.generic.rankingPlugin.classes.cache.MostCitedDois');
import('plugins.generic.rankingPlugin.classes.cache.TrendingSubmissions');

class RankingCacheUpdateTask extends ScheduledTask
{
    public function getName()
    {
        return __('plugins.generic.rankingPlugin.scheduledTask.name');
    }

    public function executeActions()
    {
        $contextDao = Application::getContextDAO();
        $contexts = $contextDao->getAll();

        while ($context = $contexts->next()) {
            if (!$context->getEnabled()) {
                continue;
            }

            $this->updateContextCaches($context);
        }

        return true;
    }

    private function updateContextCaches($context)
    {
        $request = Application::get()->getRequest();
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');

        if (!$plugin || !$plugin->getEnabled()) {
            return;
        }

        $this->addExecutionLogEntry(
            __(
                'plugins.generic.rankingPlugin.scheduledTask.updateStart',
                array('contextName' => $context->getLocalizedName())
            ),
            SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
        );

        try {
            $this->updateMostRecentCache($context, $request);
            $this->updateMostReadCache($context, $request);
            $this->updateMostCitedCache($context);
            $this->updateTrendingCache($context);

            $this->addExecutionLogEntry(
                __(
                    'plugins.generic.rankingPlugin.scheduledTask.updateComplete',
                    array('contextName' => $context->getLocalizedName())
                ),
                SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
            );
        } catch (Exception $e) {
            $this->addExecutionLogEntry(
                __(
                    'plugins.generic.rankingPlugin.scheduledTask.updateError',
                    array('contextName' => $context->getLocalizedName(), 'error' => $e->getMessage())
                ),
                SCHEDULED_TASK_MESSAGE_TYPE_ERROR
            );
        }
    }

    private function updateMostRecentCache($context, $request)
    {
        $mostRecent = new MostRecent();
        $mostRecent->refreshCache($context, $request);
    }

    private function updateMostReadCache($context, $request)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');
        $mostRead = new MostRead($plugin);
        $mostRead->refreshCache($context, $request);
    }

    private function updateMostCitedCache($context)
    {
        $issn = $context->getSetting('onlineIssn') ?: $context->getSetting('printIssn');
        if (!$issn) {
            return;
        }

        $mostCited = new MostCitedDois();
        $mostCited->refreshCache($context->getId(), $issn, 10);
    }

    private function updateTrendingCache($context)
    {
        $trending = new TrendingSubmissions();
        $trending->refreshCache($context->getId(), $context->getPath(), 10);
    }
}
