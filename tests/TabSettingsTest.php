<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\DataEncryption;
use APP\plugins\generic\rankingPlugin\classes\settings\AltmetricsApiKey;
use APP\plugins\generic\rankingPlugin\classes\settings\TabSettings;
use APP\plugins\generic\rankingPlugin\RankingPlugin;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class TabSettingsTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const ISSN = '1234-5678';

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

    private function buildInput(array $apiKeyInput = []): array
    {
        return array_merge([
            'customTitle' => [],
            'description' => [],
            'itemsPerTab' => 4,
            'itemsPerPage' => 4,
        ], $apiKeyInput);
    }

    #[Test]
    public function itShouldDelegateApiKeyValidationAndStorageForTheTrendingTab()
    {
        $settings = [];
        $input = $this->buildInput(['altmetricsApiKey' => 'my-key']);
        $altmetricsApiKey = $this->createMock(AltmetricsApiKey::class);
        $altmetricsApiKey->expects($this->once())
            ->method('validate')
            ->with($input, self::ISSN)
            ->willReturn(['altmetricsApiKey' => ['invalid']]);
        $altmetricsApiKey->expects($this->once())->method('save')->with($input);

        $tabSettings = new TabSettings($this->buildPluginMock($settings), self::CONTEXT_ID, 'trending', $altmetricsApiKey);

        $this->assertSame(['altmetricsApiKey' => ['invalid']], $tabSettings->validate($input, self::ISSN));
        $tabSettings->save($input);
    }

    #[Test]
    public function itShouldIgnoreApiKeyFieldsForNonTrendingTabs()
    {
        $settings = [];
        $altmetricsApiKey = $this->createMock(AltmetricsApiKey::class);
        $altmetricsApiKey->expects($this->never())->method('validate');
        $altmetricsApiKey->expects($this->never())->method('save');

        $tabSettings = new TabSettings($this->buildPluginMock($settings), self::CONTEXT_ID, 'mostRead', $altmetricsApiKey);
        $input = $this->buildInput(['altmetricsApiKey' => 'my-key']);

        $this->assertSame([], $tabSettings->validate($input, self::ISSN));
        $tabSettings->save($input);
        $this->assertArrayNotHasKey(AltmetricsApiKey::SETTING_NAME, $settings);
    }

    #[Test]
    public function itShouldExposeHasAltmetricsApiKeyFlagWithoutLeakingValue()
    {
        $settings = [AltmetricsApiKey::SETTING_NAME => (new DataEncryption())->encryptString('stored')];

        $values = (new TabSettings($this->buildPluginMock($settings), self::CONTEXT_ID, 'trending'))->getValues();

        $this->assertTrue($values['hasAltmetricsApiKey']);
        $this->assertArrayNotHasKey('altmetricsApiKey', $values);
    }

    #[Test]
    public function itShouldFallBackToDefaultsWhenItemCountsAreNotPositive()
    {
        $settings = [];

        (new TabSettings($this->buildPluginMock($settings), self::CONTEXT_ID, 'mostRead'))->save([
            'itemsPerTab' => '0',
            'itemsPerPage' => '-3',
            'mostReadDays' => '',
        ]);

        $this->assertSame(4, $settings['itemsPerTab_mostRead']);
        $this->assertSame(4, $settings['itemsPerPage_mostRead']);
        $this->assertSame(120, $settings['mostReadDays_mostRead']);
    }
}
