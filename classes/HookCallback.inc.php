<?php

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

        $rankingPluginApiBaseUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $context->getPath(),
            'rankingPlugin/'
        );

        $contextId = $context->getId();
        $customTitles = [];
        $customDescriptions = [];

        $tabs = ['highlight', 'mostRecent', 'mostRead', 'mostCited', 'trending'];
        foreach ($tabs as $tabId) {
            $customTitles[$tabId] = $this->plugin->getSetting(
                $contextId,
                "customTitle_{$tabId}"
            );
            $customDescriptions[$tabId] = $this->plugin->getSetting(
                $contextId,
                "customDescription_{$tabId}"
            );
        }

        $templateMgr->assign('customTitles', $customTitles);
        $templateMgr->assign('customDescriptions', $customDescriptions);

        $rankingPluginJavaScriptVariables = [
            'rankingTemplate' => $templateMgr->fetch(
                $this->plugin->getTemplateResource('ranking.tpl')
            ),
            'rankingPluginApiBaseUrl' => $rankingPluginApiBaseUrl,
            'mostRecentFailedMessage' => __(
                'plugins.generic.rankingPlugin.tabs.mostRecentFailed'
            ),
            'mostReadFailedMessage' => __(
                'plugins.generic.rankingPlugin.tabs.mostReadFailed'
            ),
            'mostCitedFailedMessage' => __(
                'plugins.generic.rankingPlugin.tabs.mostCitedFailed'
            ),
            'trendingFailedMessage' => __(
                'plugins.generic.rankingPlugin.tabs.trendingFailed'
            ),
            'noPublicationsFoundMessage' => __(
                'plugins.generic.rankingPlugin.NoPublicationsFound'
            ),
        ];

        $this->loadResources(
            $templateMgr,
            $request,
            $rankingPluginJavaScriptVariables
        );

        return false;
    }

    public function addScoreFieldToSubmissionSchema($hookName, $args)
    {
        $schema = $args[0];

        $schema->properties->{"altmetricsScore"} = (object) [
            'type' => 'number',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];

        return false;
    }

    public function setupRankingConfigurationGridHandler($hookName, $params)
    {
        $component = &$params[0];
        if ($component == 'plugins.generic.rankingPlugin.controllers.grid.RankingConfigurationGridHandler') {
            return true;
        }
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
