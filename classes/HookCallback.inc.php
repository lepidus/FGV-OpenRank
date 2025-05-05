<?php

class HookCallback
{
    private $plugin;
    private const LIMIT = 4;

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

        $templateMgr->assign([
            'mostRecentSubmissions' => $this->getMostRecentSubmissions($contextId),
            'mostViewedSubmissions' => $this->getMostViewedSubmissions($contextId),
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

    private function getMostRecentSubmissions($contextId)
    {
        $submissions = Services::get('submission')->getMany([
            'contextId' => $contextId,
            'status' => STATUS_PUBLISHED,
            'orderDirection' => 'DESC',
            'count' => self::LIMIT
        ]);

        return $submissions;
    }

    private function getMostViewedSubmissions($contextId) 
    {
        $topSubmissions = Services::get('stats')->getOrderedObjects(
            STATISTICS_DIMENSION_SUBMISSION_ID,
            STATISTICS_ORDER_DESC,
            [
                'contextIds' => [$contextId],
                'count' => self::LIMIT
            ]
        );

        $submissions = [];
        foreach ($topSubmissions as $topSubmission) {
            $submissionId = $topSubmission['id'];
            $submission = Services::get('submission')->get($submissionId);
            
            if ($submission) {
                $submissions[] = [
                    'submission' => $submission
                ];
            }
        }

        return $submissions;
    }
}
