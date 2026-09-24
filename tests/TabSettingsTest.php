<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\clients\Altmetrics;
use APP\plugins\generic\rankingPlugin\classes\DataEncryption;
use APP\plugins\generic\rankingPlugin\classes\settings\TabSettings;
use APP\plugins\generic\rankingPlugin\RankingPlugin;
use Exception;
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

    private function buildTabSettings(array &$settings, string $tabId, $dataEncryption = null, $altmetricsClient = null): TabSettings
    {
        return new class ($this->buildPluginMock($settings), self::CONTEXT_ID, $tabId, $dataEncryption, $altmetricsClient) extends TabSettings {
            protected function getContextIssn(): ?string
            {
                return '1234-5678';
            }

            protected function refreshCache(int $limit): void
            {
            }
        };
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
    public function itShouldStoreEncryptedApiKeyWhenProvided()
    {
        $settings = [];
        $encryption = $this->createMock(DataEncryption::class);
        $encryption->expects($this->once())
            ->method('encryptString')
            ->with('my-plaintext-key')
            ->willReturn('encrypted-value');

        $this->buildTabSettings($settings, 'trending', $encryption)
            ->save($this->buildInput(['altmetricsApiKey' => 'my-plaintext-key', 'removeAltmetricsApiKey' => 'false']));

        $this->assertSame('encrypted-value', $settings[TabSettings::API_KEY_SETTING]);
    }

    #[Test]
    public function itShouldClearApiKeyWhenRemoveCheckboxIsTrue()
    {
        $settings = [TabSettings::API_KEY_SETTING => 'previous-key'];
        $encryption = $this->createMock(DataEncryption::class);
        $encryption->expects($this->never())->method('encryptString');

        $this->buildTabSettings($settings, 'trending', $encryption)
            ->save($this->buildInput(['altmetricsApiKey' => '', 'removeAltmetricsApiKey' => 'true']));

        $this->assertSame('', $settings[TabSettings::API_KEY_SETTING]);
    }

    #[Test]
    public function itShouldPreserveExistingKeyWhenBothEmpty()
    {
        $settings = [TabSettings::API_KEY_SETTING => 'previous-key'];
        $encryption = $this->createMock(DataEncryption::class);
        $encryption->expects($this->never())->method('encryptString');

        $this->buildTabSettings($settings, 'trending', $encryption)
            ->save($this->buildInput(['altmetricsApiKey' => '', 'removeAltmetricsApiKey' => 'false']));

        $this->assertSame('previous-key', $settings[TabSettings::API_KEY_SETTING]);
    }

    #[Test]
    public function itShouldFailValidationWhenAltmetricRejectsTheKey()
    {
        $settings = [];
        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->once())
            ->method('fetchBestScoreSubmissions')
            ->with(self::ISSN, $this->anything(), 'bad-key')
            ->willThrowException(new Exception(__('plugins.generic.rankingPlugin.client.altmetrics.clientError')));

        $errors = $this->buildTabSettings($settings, 'trending', null, $altmetricsClient)
            ->validate($this->buildInput(['altmetricsApiKey' => 'bad-key']));

        $this->assertArrayHasKey('altmetricsApiKey', $errors);
        $this->assertArrayNotHasKey(TabSettings::API_KEY_SETTING, $settings);
    }

    #[Test]
    public function itShouldPassValidationWhenAltmetricAcceptsTheKey()
    {
        $settings = [];
        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->once())
            ->method('fetchBestScoreSubmissions')
            ->with(self::ISSN, $this->anything(), 'good-key')
            ->willReturn(['results' => []]);

        $errors = $this->buildTabSettings($settings, 'trending', null, $altmetricsClient)
            ->validate($this->buildInput(['altmetricsApiKey' => 'good-key']));

        $this->assertSame([], $errors);
    }

    #[Test]
    public function itShouldNotCallAltmetricWhenKeyIsBeingRemoved()
    {
        $settings = [TabSettings::API_KEY_SETTING => 'previous-key'];
        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->never())->method('fetchBestScoreSubmissions');

        $errors = $this->buildTabSettings($settings, 'trending', null, $altmetricsClient)
            ->validate($this->buildInput(['altmetricsApiKey' => 'some-key', 'removeAltmetricsApiKey' => 'true']));

        $this->assertSame([], $errors);
    }

    #[Test]
    public function itShouldIgnoreApiKeyFieldsForNonTrendingTabs()
    {
        $settings = [];
        $encryption = $this->createMock(DataEncryption::class);
        $encryption->expects($this->never())->method('encryptString');
        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->never())->method('fetchBestScoreSubmissions');

        $tabSettings = $this->buildTabSettings($settings, 'mostRead', $encryption, $altmetricsClient);
        $input = $this->buildInput(['altmetricsApiKey' => 'my-key']);

        $this->assertSame([], $tabSettings->validate($input));
        $tabSettings->save($input);
        $this->assertArrayNotHasKey(TabSettings::API_KEY_SETTING, $settings);
    }

    #[Test]
    public function itShouldExposeHasAltmetricsApiKeyFlagWithoutLeakingValue()
    {
        $settings = [TabSettings::API_KEY_SETTING => 'stored'];

        $values = $this->buildTabSettings($settings, 'trending')->getValues();

        $this->assertTrue($values['hasAltmetricsApiKey']);
        $this->assertArrayNotHasKey('altmetricsApiKey', $values);
    }

    #[Test]
    public function itShouldFallBackToDefaultsWhenItemCountsAreNotPositive()
    {
        $settings = [];

        $this->buildTabSettings($settings, 'mostRead')->save([
            'itemsPerTab' => '0',
            'itemsPerPage' => '-3',
            'mostReadDays' => '',
        ]);

        $this->assertSame(4, $settings['itemsPerTab_mostRead']);
        $this->assertSame(4, $settings['itemsPerPage_mostRead']);
        $this->assertSame(120, $settings['mostReadDays_mostRead']);
    }
}
