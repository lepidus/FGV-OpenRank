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

    public function addFormValidators()
    {
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager();
        $templateMgr->assign('tabId', $this->tabId);

        $this->initData();
        $templateMgr->assign('customTitle', $this->getData('customTitle'));
        $templateMgr->assign('description', $this->getData('description'));
        $templateMgr->assign('itemsPerTab', $this->getData('itemsPerTab'));
        $templateMgr->assign('itemsPerPage', $this->getData('itemsPerPage'));

        if ($this->tabId === 'mostRead') {
            $templateMgr->assign('mostReadDays', $this->getData('mostReadDays'));
        }

        return parent::fetch($request);
    }

    public function readInputData()
    {
        $userVars = array(
            'customTitle',
            'description',
            'itemsPerTab',
            'itemsPerPage'
        );
        if ($this->tabId === 'mostRead') {
            $userVars[] = 'mostReadDays';
        }
        $this->readUserVars($userVars);
        parent::readInputData();
    }

    public function initData()
    {
        if ($this->tabId) {
            $customTitle = $this->plugin->getSetting(
                $this->contextId,
                "customTitle_{$this->tabId}"
            );
            $description = $this->plugin->getSetting(
                $this->contextId,
                "customDescription_{$this->tabId}"
            );
            $itemsPerTab = $this->plugin->getSetting(
                $this->contextId,
                "itemsPerTab_{$this->tabId}"
            ) ?? 4;
            $itemsPerPage = $this->plugin->getSetting(
                $this->contextId,
                "itemsPerPage_{$this->tabId}"
            ) ?? 4;

            $this->setData('customTitle', $customTitle);
            $this->setData('description', $description);
            $this->setData('itemsPerTab', $itemsPerTab);
            $this->setData('itemsPerPage', $itemsPerPage);

            if ($this->tabId === 'mostRead') {
                $mostReadDays = $this->plugin->getSetting(
                    $this->contextId,
                    "mostReadDays_{$this->tabId}"
                ) ?? 120;
                $this->setData('mostReadDays', $mostReadDays);
            }
        }
        parent::initData();
    }

    public function execute(...$functionArgs)
    {
        $customTitle = $this->getData('customTitle');
        $description = $this->getData('description');
        $itemsPerTab = (int) $this->getData('itemsPerTab');
        $itemsPerPage = (int) $this->getData('itemsPerPage');

        if ($itemsPerTab < 1) {
            $itemsPerTab = 4;
        }
        if ($itemsPerPage < 1) {
            $itemsPerPage = 4;
        }

        if ($this->tabId) {
            $this->plugin->updateSetting(
                $this->contextId,
                "customTitle_{$this->tabId}",
                $customTitle,
                'object'
            );

            $this->plugin->updateSetting(
                $this->contextId,
                "customDescription_{$this->tabId}",
                $description,
                'object'
            );

            $this->plugin->updateSetting(
                $this->contextId,
                "itemsPerTab_{$this->tabId}",
                $itemsPerTab,
                'int'
            );

            $this->plugin->updateSetting(
                $this->contextId,
                "itemsPerPage_{$this->tabId}",
                $itemsPerPage,
                'int'
            );

            if ($this->tabId === 'mostRead') {
                $mostReadDays = (int) $this->getData('mostReadDays');
                if ($mostReadDays < 1) {
                    $mostReadDays = 120;
                }
                $this->plugin->updateSetting(
                    $this->contextId,
                    "mostReadDays_{$this->tabId}",
                    $mostReadDays,
                    'int'
                );
            }
        }

        parent::execute(...$functionArgs);
    }
}
