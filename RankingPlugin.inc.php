<?php

import('lib.pkp.classes.plugins.GenericPlugin');

class RankingPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);
        if ($success && $this->getEnabled()) {
            HookRegistry::register('TemplateManager::display', array($this, 'handleMetricsData'));
        }
        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.rankingPlugin.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.rankingPlugin.description');
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

        $limit = 10;

        $mostRecentSubmissionsIterator = Services::get('submission')->getMany([
            'contextId' => $contextId,
            'status' => STATUS_PUBLISHED,
            'orderDirection' => 'DESC',
            'count' => $limit
        ]);

        $submissionsInSections = [];
        foreach ($mostRecentSubmissionsIterator as $submission) {
            $submissionsInSections[]['articles'][] = $submission;
        }

        $templateMgr->assign('mostRecentSubmissions', $submissionsInSections);

        $data = [
            'rankingBlock' => $templateMgr->fetch($this->getTemplateResource('ranking.tpl'))
        ];

        $templateMgr->addJavaScript(
            'AppData',
            'app = ' . json_encode($data) . ';',
            [
                'inline' => true,
            ]
        );

        $templateMgr->addJavaScript(
            'rankingPluginScript',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/insertRankingBlock.js',
            ['priority' => STYLE_SEQUENCE_LAST]
        );

        $templateMgr->addStyleSheet(
            'rankingPluginStyles',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/ranking.css',
            ['priority' => STYLE_SEQUENCE_LAST]
        );

        return false;
    }
}
