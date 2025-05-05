<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmission');

class HookCallback
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function handleMetricsData($hookName, $args)
    {
        $template = $args[1];

        if ($template !== 'frontend/pages/indexJournal.tpl') {
            return false;
        }

        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : CONTEXT_ID_NONE;
        $rankingSubmission = new RankingSubmission($contextId);

        $templateMgr->assign([
            'mostRecentSubmissions' => $rankingSubmission->getMostRecent(),
            'mostViewedSubmissions' => $rankingSubmission->getMostViewed(),
            'context' => $context
        ]);

        $rankingTemplate = [
            'rankingTemplate' => $templateMgr->fetch($this->plugin->getTemplateResource('ranking.tpl'))
        ];

        $this->loadResources($templateMgr, $request, $rankingTemplate);

        return false;
    }

    private function loadResources($templateMgr, $request, $rankingTemplate)
    {
        $templateMgr->addJavaScript(
            'AppData',
            'app = ' . json_encode($rankingTemplate) . ';',
            ['inline' => true]
        );

        $templateMgr->addJavaScript(
            'rankingPluginScript',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/insertRankingTemplate.js',
            ['priority' => STYLE_SEQUENCE_LAST]
        );

        $templateMgr->addStyleSheet(
            'rankingPluginStyles',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/styles/ranking.css',
            ['priority' => STYLE_SEQUENCE_LAST]
        );
    }
}
