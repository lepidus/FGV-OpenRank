<?php

import('lib.pkp.classes.plugins.GenericPlugin');

class RankingPlugin extends GenericPlugin {

	public function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
		}
		return $success;
	}

	public function getDisplayName() {
		return __('plugins.generic.rankingPlugin.displayName');
	}

	public function getDescription() {
		return __('plugins.generic.rankingPlugin.description');
	}
}