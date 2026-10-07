<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.fgvOpenRank.classes.RankingDisplayPosition');
import('plugins.generic.fgvOpenRank.classes.settings.FgvOpenRankSettingsForm');
import('plugins.generic.fgvOpenRank.FgvOpenRankPlugin');

class FgvOpenRankSettingsFormTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;

    private function buildPluginMock(&$settings)
    {
        $plugin = $this->createMock(FgvOpenRankPlugin::class);
        $plugin->method('getTemplateResource')->willReturn('form.tpl');
        $plugin->method('getName')->willReturn('fgvopenrankplugin');
        $plugin->method('getSetting')
            ->willReturnCallback(function ($contextId, $key) use (&$settings) {
                return $settings[$key] ?? null;
            });
        $plugin->method('updateSetting')
            ->willReturnCallback(function ($contextId, $key, $value) use (&$settings) {
                $settings[$key] = $value;
                return true;
            });
        return $plugin;
    }

    /**
     * @test
     */
    public function itShouldKeepItsValidatorsAfterConstruction()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);

        $checks = array_map('get_class', $form->_checks);

        $this->assertContains('FormValidatorPost', $checks);
        $this->assertContains('FormValidatorCSRF', $checks);
    }

    /**
     * @test
     */
    public function itShouldStartWithAdditionalContentWhenNothingIsStored()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);
        $form->initData();

        $this->assertSame(
            RankingDisplayPosition::ADDITIONAL_CONTENT,
            $form->getData(RankingDisplayPosition::SETTING_NAME)
        );
    }

    /**
     * @test
     */
    public function itShouldStartWithTheStoredPosition()
    {
        $settings = [
            RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::TOP
        ];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);
        $form->initData();

        $this->assertSame(
            RankingDisplayPosition::TOP,
            $form->getData(RankingDisplayPosition::SETTING_NAME)
        );
    }

    /**
     * @test
     */
    public function itShouldPersistTheSelectedPosition()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);
        $form->setData(
            RankingDisplayPosition::SETTING_NAME,
            RankingDisplayPosition::TOP
        );
        $form->execute();

        $this->assertSame(
            RankingDisplayPosition::TOP,
            $settings[RankingDisplayPosition::SETTING_NAME]
        );
    }

    /**
     * @test
     */
    public function itShouldStartWithTheFirstSectionWhenNothingIsStored()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);
        $form->initData();

        $this->assertSame(
            1,
            $form->getData(RankingDisplayPosition::SECTION_SETTING_NAME)
        );
    }

    /**
     * @test
     */
    public function itShouldStartWithTheStoredSection()
    {
        $settings = [RankingDisplayPosition::SECTION_SETTING_NAME => 3];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);
        $form->initData();

        $this->assertSame(
            3,
            $form->getData(RankingDisplayPosition::SECTION_SETTING_NAME)
        );
    }

    /**
     * @test
     */
    public function itShouldPersistTheSelectedSection()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);
        $form->setData(
            RankingDisplayPosition::SETTING_NAME,
            RankingDisplayPosition::AFTER_SECTION
        );
        $form->setData(RankingDisplayPosition::SECTION_SETTING_NAME, '4');
        $form->execute();

        $this->assertSame(
            4,
            $settings[RankingDisplayPosition::SECTION_SETTING_NAME]
        );
    }

    /**
     * @test
     */
    public function itShouldPersistTheFirstSectionWhenSubmittedSectionIsInvalid()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);
        $form->setData(
            RankingDisplayPosition::SETTING_NAME,
            RankingDisplayPosition::AFTER_SECTION
        );
        $form->setData(RankingDisplayPosition::SECTION_SETTING_NAME, '0');
        $form->execute();

        $this->assertSame(
            1,
            $settings[RankingDisplayPosition::SECTION_SETTING_NAME]
        );
    }

    /**
     * @test
     */
    public function itShouldPersistTheDefaultWhenSubmittedPositionIsUnknown()
    {
        $settings = [
            RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::TOP
        ];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);
        $form->setData(RankingDisplayPosition::SETTING_NAME, 'sidebar');
        $form->execute();

        $this->assertSame(
            RankingDisplayPosition::ADDITIONAL_CONTENT,
            $settings[RankingDisplayPosition::SETTING_NAME]
        );
    }

    /**
     * @test
     */
    public function itShouldOfferEveryPositionToTheTemplate()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new FgvOpenRankSettingsForm($plugin, self::CONTEXT_ID);

        $this->assertSame(
            [
                RankingDisplayPosition::TOP
                    => 'plugins.generic.fgvOpenRank.settings.displayPosition.top',
                RankingDisplayPosition::AFTER_SECTION
                    => 'plugins.generic.fgvOpenRank.settings.displayPosition.afterSection',
                RankingDisplayPosition::BOTTOM
                    => 'plugins.generic.fgvOpenRank.settings.displayPosition.bottom',
                RankingDisplayPosition::ADDITIONAL_CONTENT
                    => 'plugins.generic.fgvOpenRank.settings.displayPosition.additionalContent',
            ],
            $form->getPositionOptions()
        );
    }
}
