<?php

import('lib.pkp.classes.form.Form');

class RankingCustomizationForm extends Form
{
    private $plugin;
    private $contextId;
    private $tabId;

    public function __construct($plugin, $contextId, $tabId = null)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;
        $this->tabId = $tabId;
        $this->addFormValidators();

        $template = 'customization/form.tpl';
        parent::__construct($plugin->getTemplateResource($template));
    }

    private function addFormValidators(): void
    {
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager();
        $templateMgr->assign('tabId', $this->tabId);
        $templateMgr->assign('customTitle', $this->plugin->getSetting($this->contextId, "customTitle_{$this->tabId}"));
        $templateMgr->assign('description', $this->plugin->getSetting($this->contextId, "customDescription_{$this->tabId}"));
        return parent::fetch($request);
    }

    public function readInputData()
    {
        $this->readUserVars(array('customTitle', 'description'));
        parent::readInputData();
    }

    public function execute(...$functionArgs)
    {
        $customTitle = $this->getData('customTitle');
        $description = $this->getData('description');

        if ($this->tabId) {
            if ($customTitle) {
                $this->plugin->updateSetting($this->contextId, "customTitle_{$this->tabId}", $customTitle);
            } else {
                $this->plugin->updateSetting($this->contextId, "customTitle_{$this->tabId}", null);
            }

            if ($description) {
                $this->plugin->updateSetting($this->contextId, "customDescription_{$this->tabId}", $description);
            } else {
                $this->plugin->updateSetting($this->contextId, "customDescription_{$this->tabId}", null);
            }
        }

        parent::execute(...$functionArgs);
    }

    public function initData(): void
    {
        if ($this->tabId) {
            $customTitle = $this->plugin->getSetting($this->contextId, "customTitle_{$this->tabId}");
            $description = $this->plugin->getSetting($this->contextId, "customDescription_{$this->tabId}");

            $this->setData('customTitle', $customTitle);
            $this->setData('description', $description);
        }
    }
}
