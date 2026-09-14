<?php

import('lib.pkp.classes.form.Form');
import('plugins.generic.rankingPlugin.classes.RankingDisplayPosition');

class RankingPluginSettingsForm extends Form
{
    private $plugin;
    private $contextId;

    public function __construct($plugin, $contextId)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;

        $template = 'settings/form.tpl';
        parent::__construct($plugin->getTemplateResource($template));

        $this->addFormValidators();
    }

    private function addFormValidators(): void
    {
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function getPositionOptions(): array
    {
        $options = [];
        foreach (RankingDisplayPosition::getAll() as $position) {
            $options[$position] =
                "plugins.generic.rankingPlugin.settings.displayPosition.{$position}";
        }

        return $options;
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        $templateMgr->assign(
            'displayPosition',
            $this->getData(RankingDisplayPosition::SETTING_NAME)
        );
        $templateMgr->assign(
            'displayPositionSection',
            $this->getData(RankingDisplayPosition::SECTION_SETTING_NAME)
        );
        $templateMgr->assign('displayPositionOptions', $this->getPositionOptions());

        return parent::fetch($request);
    }

    public function readInputData()
    {
        $this->readUserVars([
            RankingDisplayPosition::SETTING_NAME,
            RankingDisplayPosition::SECTION_SETTING_NAME,
        ]);
        parent::readInputData();
    }

    public function execute(...$functionArgs)
    {
        $this->plugin->updateSetting(
            $this->contextId,
            RankingDisplayPosition::SETTING_NAME,
            RankingDisplayPosition::normalize(
                $this->getData(RankingDisplayPosition::SETTING_NAME)
            ),
            'string'
        );

        $this->plugin->updateSetting(
            $this->contextId,
            RankingDisplayPosition::SECTION_SETTING_NAME,
            RankingDisplayPosition::normalizeSection(
                $this->getData(RankingDisplayPosition::SECTION_SETTING_NAME)
            ),
            'int'
        );

        parent::execute(...$functionArgs);
    }

    public function initData(): void
    {
        $this->setData(
            RankingDisplayPosition::SETTING_NAME,
            RankingDisplayPosition::normalize(
                $this->plugin->getSetting(
                    $this->contextId,
                    RankingDisplayPosition::SETTING_NAME
                )
            )
        );

        $this->setData(
            RankingDisplayPosition::SECTION_SETTING_NAME,
            RankingDisplayPosition::normalizeSection(
                $this->plugin->getSetting(
                    $this->contextId,
                    RankingDisplayPosition::SECTION_SETTING_NAME
                )
            )
        );

        parent::initData();
    }
}
