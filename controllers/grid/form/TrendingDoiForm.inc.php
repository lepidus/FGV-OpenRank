<?php

import('lib.pkp.classes.form.Form');

class TrendingDoiForm extends Form
{
    private const DOI_REGEX = '/^10\.\d{4,}\/\S+$/';
    private const SETTING_NAME = 'trendingDois_trending';

    private $plugin;
    private $contextId;
    private $optionId;
    private $submissionDao;

    public function __construct($plugin, $contextId, $optionId = null, $submissionDao = null)
    {
        if (!$plugin) {
            fatalError('Plugin is required');
        }

        $this->plugin = $plugin;
        $this->contextId = $contextId;
        $this->optionId = $optionId;
        $this->submissionDao = $submissionDao;

        parent::__construct($plugin->getTemplateResource('grid/trendingDoiForm.tpl'));

        $this->addCheck(new FormValidator(
            $this,
            'doi',
            'required',
            'plugins.generic.rankingPlugin.trendingDois.invalidDoi'
        ));
        $this->addCheck(new FormValidatorRegExp(
            $this,
            'doi',
            'required',
            'plugins.generic.rankingPlugin.trendingDois.invalidDoi',
            self::DOI_REGEX
        ));
        $this->addSecurityValidators();
    }

    protected function addSecurityValidators()
    {
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function initData()
    {
        if ($this->optionId !== null && $this->optionId !== '' && $this->optionId !== '0') {
            $dois = $this->plugin->getSetting($this->contextId, self::SETTING_NAME) ?: [];
            if (isset($dois[$this->optionId])) {
                $this->setData('doi', $dois[$this->optionId]);
            }
        }
    }

    public function readInputData()
    {
        $this->readUserVars(['doi']);
    }

    public function validate($callHooks = true)
    {
        $this->validateDoiExistsInContext();
        return parent::validate($callHooks);
    }

    private function validateDoiExistsInContext(): void
    {
        $doi = trim((string) $this->getData('doi'));
        if ($doi === '' || !preg_match(self::DOI_REGEX, $doi)) {
            return;
        }
        $submission = $this->getSubmissionDao()->getByPubId('doi', $doi, $this->contextId);
        if (!$submission) {
            $this->addError(
                'doi',
                __('plugins.generic.rankingPlugin.trendingDois.doiNotInJournal')
            );
            $this->addErrorField('doi');
        }
    }

    private function getSubmissionDao()
    {
        if ($this->submissionDao === null) {
            $this->submissionDao = DAORegistry::getDAO('SubmissionDAO');
        }
        return $this->submissionDao;
    }

    public function execute(...$functionArgs)
    {
        $dois = $this->plugin->getSetting($this->contextId, self::SETTING_NAME) ?: [];
        $doi = trim($this->getData('doi'));

        if ($this->optionId === null || $this->optionId === '' || $this->optionId === '0') {
            do {
                $optionId = uniqid();
            } while (isset($dois[$optionId]));
        } else {
            $optionId = $this->optionId;
        }

        $dois[$optionId] = $doi;
        $this->plugin->updateSetting($this->contextId, self::SETTING_NAME, $dois);

        return $optionId;
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('optionId', $this->optionId);
        $templateMgr->assign('doi', $this->getData('doi'));
        return parent::fetch($request, $template, $display);
    }
}
