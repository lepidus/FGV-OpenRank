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

        $templateMgr->assign([
            'mostRecentSubmissions' => $rankingSubmissionService->getMostRecent(),
            'mostViewedSubmissions' => $rankingSubmissionService->getMostViewed(),
            'context' => $context
        ]);

        $rankingPluginApiBaseUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $context->getPath(),
            'rankingPlugin/'
        );

        $rankingPluginJavaScriptVariables = [
            'rankingTemplate' => $templateMgr->fetch($this->plugin->getTemplateResource('ranking.tpl')),
            'rankingPluginApiBaseUrl' => $rankingPluginApiBaseUrl,
            'mostCitedFailedMessage' => __('plugins.generic.rankingPlugin.tabs.mostCitedFailed'),
            'noPublicationsFoundMessage' => __('plugins.generic.rankingPlugin.NoPublicationsFound'),
        ];

        $this->loadResources($templateMgr, $request, $rankingPluginJavaScriptVariables);

        return false;
    }

    public function addScoreFieldToPublicationSchema($hookName, $args)
    {
        $schema = $args[0];

        $schema->properties->{"altmetricsScore"} = (object) [
            'type' => 'float',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];

        return false;
    }

    private function loadResources($templateMgr, $request, $rankingPluginJavaScriptVariables)
    {
        $templateMgr->addJavaScript(
            'AppData',
            'app = ' . json_encode($rankingPluginJavaScriptVariables) . ';',
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
