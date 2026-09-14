<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.rankingPlugin.classes.RankingDisplayPosition');
import('plugins.generic.rankingPlugin.classes.settings.RankingPluginSettingsForm');
import('plugins.generic.rankingPlugin.RankingPlugin');

class RankingPluginSettingsFormTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;

    private function buildPluginMock(&$settings)
    {
        $plugin = $this->createMock(RankingPlugin::class);
        $plugin->method('getTemplateResource')->willReturn('form.tpl');
        $plugin->method('getName')->willReturn('rankingplugin');
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
    public function itShouldStartWithAdditionalContentWhenNothingIsStored()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new RankingPluginSettingsForm($plugin, self::CONTEXT_ID);
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

        $form = new RankingPluginSettingsForm($plugin, self::CONTEXT_ID);
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

        $form = new RankingPluginSettingsForm($plugin, self::CONTEXT_ID);
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

        $form = new RankingPluginSettingsForm($plugin, self::CONTEXT_ID);
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

        $form = new RankingPluginSettingsForm($plugin, self::CONTEXT_ID);
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

        $form = new RankingPluginSettingsForm($plugin, self::CONTEXT_ID);
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

        $form = new RankingPluginSettingsForm($plugin, self::CONTEXT_ID);
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

        $form = new RankingPluginSettingsForm($plugin, self::CONTEXT_ID);
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

        $form = new RankingPluginSettingsForm($plugin, self::CONTEXT_ID);

        $this->assertSame(
            [
                RankingDisplayPosition::TOP
                    => 'plugins.generic.rankingPlugin.settings.displayPosition.top',
                RankingDisplayPosition::AFTER_SECTION
                    => 'plugins.generic.rankingPlugin.settings.displayPosition.afterSection',
                RankingDisplayPosition::BOTTOM
                    => 'plugins.generic.rankingPlugin.settings.displayPosition.bottom',
                RankingDisplayPosition::ADDITIONAL_CONTENT
                    => 'plugins.generic.rankingPlugin.settings.displayPosition.additionalContent',
            ],
            $form->getPositionOptions()
        );
    }
}
