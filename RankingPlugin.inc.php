<?php

import('lib.pkp.classes.plugins.GenericPlugin');

class RankingPlugin extends GenericPlugin {

	public function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			HookRegistry::register('TemplateManager::display', array($this, 'handleMetricsData'));
		}
		return $success;
	}

	public function getDisplayName() {
		return __('plugins.generic.rankingPlugin.displayName');
	}

	public function getDescription() {
		return __('plugins.generic.rankingPlugin.description');
	}

	public function handleMetricsData($hookName, $args) {
        $template = $args[1];

		if ($template != 'frontend/pages/indexJournal.tpl') {
			return false;
		}

		$templateMgr = $args[0];
		$request = Application::get()->getRequest();

		$limit = 10;

		$mostRecentSubmissionsIterator = Services::get('submission')->getMany(
            [
                'contextId' => '1',
                'status' => STATUS_PUBLISHED,
                'orderDirection' => 'DESC',
                'count' => $limit
            ]
        );

        $submissionsInSections = [];
        foreach ($mostRecentSubmissionsIterator as $submission) {
			$submissionsInSections[]['articles'][] = $submission;
        }

		$templateMgr->assign('mostRecentSubmissions', $submissionsInSections);
	}
}
