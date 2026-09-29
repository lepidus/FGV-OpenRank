<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\components\forms\DisplayPositionForm;
use APP\plugins\generic\rankingPlugin\classes\RankingDisplayPosition;
use APP\plugins\generic\rankingPlugin\classes\settings\DisplayPositionSettings;
use APP\plugins\generic\rankingPlugin\RankingPlugin;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class DisplayPositionSettingsTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const ACTION = 'http://localhost/index.php/journal/api/v1/plugins/rankingplugin/settings';

    private function buildPluginMock(array &$settings)
    {
        $plugin = $this->createMock(RankingPlugin::class);
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

    private function getFieldValue(array $settings, string $fieldName)
    {
        $form = new DisplayPositionForm(self::ACTION, $this->getStoredValues($settings));
        foreach ($form->getConfig()['fields'] as $field) {
            if ($field['name'] === $fieldName) {
                return $field['value'];
            }
        }
        return null;
    }

    private function getStoredValues(array &$settings): array
    {
        return (new DisplayPositionSettings($this->buildPluginMock($settings), self::CONTEXT_ID))->get();
    }

    private function save(array &$settings, array $input): void
    {
        (new DisplayPositionSettings($this->buildPluginMock($settings), self::CONTEXT_ID))->save($input);
    }

    #[Test]
    public function itShouldStartWithAdditionalContentWhenNothingIsStored()
    {
        $this->assertSame(
            RankingDisplayPosition::ADDITIONAL_CONTENT,
            $this->getFieldValue([], RankingDisplayPosition::SETTING_NAME)
        );
    }

    #[Test]
    public function itShouldStartWithTheStoredPosition()
    {
        $this->assertSame(
            RankingDisplayPosition::BOTTOM,
            $this->getFieldValue([RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::BOTTOM], RankingDisplayPosition::SETTING_NAME)
        );
    }

    #[Test]
    public function itShouldStartWithTheFirstSectionWhenNothingIsStored()
    {
        $this->assertSame(1, $this->getFieldValue([], RankingDisplayPosition::SECTION_SETTING_NAME));
    }

    #[Test]
    public function itShouldStartWithTheStoredSection()
    {
        $this->assertSame(
            3,
            $this->getFieldValue([RankingDisplayPosition::SECTION_SETTING_NAME => 3], RankingDisplayPosition::SECTION_SETTING_NAME)
        );
    }

    #[Test]
    public function itShouldOnlyShowTheSectionFieldForAfterSection()
    {
        $settings = [];
        $form = new DisplayPositionForm(self::ACTION, $this->getStoredValues($settings));
        $sectionField = array_values(array_filter(
            $form->getConfig()['fields'],
            fn ($field) => $field['name'] === RankingDisplayPosition::SECTION_SETTING_NAME
        ))[0];

        $this->assertSame(
            [RankingDisplayPosition::SETTING_NAME, RankingDisplayPosition::AFTER_SECTION],
            $sectionField['showWhen']
        );
    }

    #[Test]
    public function itShouldOfferEveryPosition()
    {
        $this->assertSame(
            RankingDisplayPosition::getAll(),
            array_column(DisplayPositionForm::getPositionOptions(), 'value')
        );
    }

    #[Test]
    public function itShouldPersistTheSelectedPosition()
    {
        $settings = [];
        $this->save($settings, [RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::TOP]);

        $this->assertSame(RankingDisplayPosition::TOP, $settings[RankingDisplayPosition::SETTING_NAME]);
    }

    #[Test]
    public function itShouldPersistTheSelectedSection()
    {
        $settings = [];
        $this->save($settings, [
            RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::AFTER_SECTION,
            RankingDisplayPosition::SECTION_SETTING_NAME => '4',
        ]);

        $this->assertSame(4, $settings[RankingDisplayPosition::SECTION_SETTING_NAME]);
    }

    #[Test]
    public function itShouldPersistTheFirstSectionWhenSubmittedSectionIsInvalid()
    {
        $settings = [];
        $this->save($settings, [
            RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::AFTER_SECTION,
            RankingDisplayPosition::SECTION_SETTING_NAME => '0',
        ]);

        $this->assertSame(1, $settings[RankingDisplayPosition::SECTION_SETTING_NAME]);
    }

    #[Test]
    public function itShouldPersistTheDefaultWhenSubmittedPositionIsUnknown()
    {
        $settings = [RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::TOP];
        $this->save($settings, [RankingDisplayPosition::SETTING_NAME => 'sidebar']);

        $this->assertSame(RankingDisplayPosition::ADDITIONAL_CONTENT, $settings[RankingDisplayPosition::SETTING_NAME]);
    }
}
