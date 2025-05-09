<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');

class HookCallback
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function setupRankingPluginAPIHandler(string $hookname, Request $request)
    {
        $router = $request->getRouter();
        if (!($router instanceof \APIRouter)) {
            return;
        }

        if (str_contains($request->getRequestPath(), 'api/v1/rankingPlugin')) {
            $this->plugin->import('api.v1.rankingPlugin.RankingPluginHandler');
            $handler = new RankingPluginHandler();
        }

        if (!isset($handler)) {
            return;
        }

        $router->setHandler($handler);
        $handler->getApp()->run();
        exit;
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
        $rankingSubmissionService = new RankingSubmissionService($contextId);
        $issn = $context->getData('onlineIssn') ?: $context->getData('printIssn');

        if (!empty($issn)) {
            $templateMgr->assign(
                'mostCitedSubmissions',
                $rankingSubmissionService->getMostCited($issn)
            );
        }

        $templateMgr->assign([
            'mostRecentSubmissions' => $rankingSubmissionService->getMostRecent(),
            'mostViewedSubmissions' => $rankingSubmissionService->getMostViewed(),
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
