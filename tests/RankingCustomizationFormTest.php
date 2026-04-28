<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.rankingPlugin.controllers.grid.form.RankingCustomizationForm');
import('plugins.generic.rankingPlugin.classes.clients.Altmetrics');
import('plugins.generic.rankingPlugin.lib.APIKeyEncryption.APIKeyEncryption');
import('plugins.generic.rankingPlugin.RankingPlugin');

class TestableRankingCustomizationForm extends RankingCustomizationForm
{
    public function addFormValidators()
    {
    }

    protected function refreshCache($tabId, $contextId, $limit)
    {
    }

    protected function getContextIssn(): ?string
    {
        return '1234-5678';
    }
}

class RankingCustomizationFormTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;

    private function buildPluginMock(&$settings)
    {
        $plugin = $this->createMock(RankingPlugin::class);
        $plugin->method('getTemplateResource')->willReturn('form.tpl');
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
    public function itShouldStoreEncryptedApiKeyWhenProvided()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);
        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->method('secretConfigExists')->willReturn(true);
        $encryption->expects($this->once())
            ->method('encryptString')
            ->with('my-plaintext-key')
            ->willReturn('base64:encrypted-value');

        $form = new TestableRankingCustomizationForm($plugin, self::CONTEXT_ID, 'trending', $encryption);
        $form->setData('customTitle', []);
        $form->setData('description', []);
        $form->setData('itemsPerTab', 4);
        $form->setData('itemsPerPage', 4);
        $form->setData('altmetricsApiKey', 'my-plaintext-key');
        $form->setData('removeAltmetricsApiKey', false);
        $form->execute();

        $this->assertSame('base64:encrypted-value', $settings['altmetricsApiKey_trending']);
    }

    /**
     * @test
     */
    public function itShouldClearApiKeyWhenRemoveCheckboxIsTrue()
    {
        $settings = ['altmetricsApiKey_trending' => 'base64:previous-key'];
        $plugin = $this->buildPluginMock($settings);
        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->method('secretConfigExists')->willReturn(true);
        $encryption->expects($this->never())->method('encryptString');

        $form = new TestableRankingCustomizationForm($plugin, self::CONTEXT_ID, 'trending', $encryption);
        $form->setData('customTitle', []);
        $form->setData('description', []);
        $form->setData('itemsPerTab', 4);
        $form->setData('itemsPerPage', 4);
        $form->setData('altmetricsApiKey', '');
        $form->setData('removeAltmetricsApiKey', true);
        $form->execute();

        $this->assertSame('', $settings['altmetricsApiKey_trending']);
    }

    /**
     * @test
     */
    public function itShouldPreserveExistingKeyWhenBothEmpty()
    {
        $settings = ['altmetricsApiKey_trending' => 'base64:previous-key'];
        $plugin = $this->buildPluginMock($settings);
        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->method('secretConfigExists')->willReturn(true);
        $encryption->expects($this->never())->method('encryptString');

        $form = new TestableRankingCustomizationForm($plugin, self::CONTEXT_ID, 'trending', $encryption);
        $form->setData('customTitle', []);
        $form->setData('description', []);
        $form->setData('itemsPerTab', 4);
        $form->setData('itemsPerPage', 4);
        $form->setData('altmetricsApiKey', '');
        $form->setData('removeAltmetricsApiKey', false);
        $form->execute();

        $this->assertSame('base64:previous-key', $settings['altmetricsApiKey_trending']);
    }

    /**
     * @test
     */
    public function itShouldFailValidationWhenSecretMissingAndKeyProvided()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);
        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->method('secretConfigExists')->willReturn(false);

        $form = new TestableRankingCustomizationForm($plugin, self::CONTEXT_ID, 'trending', $encryption);
        $form->setData('altmetricsApiKey', 'my-key');
        $form->setData('removeAltmetricsApiKey', false);

        $valid = $form->validate();

        $this->assertFalse($valid);
        $errors = $form->getErrorsArray();
        $this->assertArrayHasKey('altmetricsApiKey', $errors);
    }

    /**
     * @test
     */
    public function itShouldFailValidationWhenAltmetricRejectsTheKey()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);
        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->method('secretConfigExists')->willReturn(true);

        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->once())
            ->method('fetchBestScoreSubmissions')
            ->with('1234-5678', $this->anything(), 'bad-key')
            ->willThrowException(new \Exception(
                __('plugins.generic.rankingPlugin.client.altmetrics.clientError')
            ));

        $form = new TestableRankingCustomizationForm(
            $plugin,
            self::CONTEXT_ID,
            'trending',
            $encryption,
            $altmetricsClient
        );
        $form->setData('altmetricsApiKey', 'bad-key');
        $form->setData('removeAltmetricsApiKey', false);

        $valid = $form->validate();

        $this->assertFalse($valid);
        $errors = $form->getErrorsArray();
        $this->assertArrayHasKey('altmetricsApiKey', $errors);
        $this->assertArrayNotHasKey('altmetricsApiKey_trending', $settings);
    }

    /**
     * @test
     */
    public function itShouldPassValidationWhenAltmetricAcceptsTheKey()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);
        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->method('secretConfigExists')->willReturn(true);

        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->once())
            ->method('fetchBestScoreSubmissions')
            ->with('1234-5678', $this->anything(), 'good-key')
            ->willReturn(['results' => []]);

        $form = new TestableRankingCustomizationForm(
            $plugin,
            self::CONTEXT_ID,
            'trending',
            $encryption,
            $altmetricsClient
        );
        $form->setData('customTitle', []);
        $form->setData('description', []);
        $form->setData('itemsPerTab', 4);
        $form->setData('itemsPerPage', 4);
        $form->setData('altmetricsApiKey', 'good-key');
        $form->setData('removeAltmetricsApiKey', false);

        $valid = $form->validate();

        $this->assertTrue($valid);
        $errors = $form->getErrorsArray();
        $this->assertArrayNotHasKey('altmetricsApiKey', $errors);
    }

    /**
     * @test
     */
    public function itShouldNotCallAltmetricWhenKeyIsBeingRemoved()
    {
        $settings = ['altmetricsApiKey_trending' => 'base64:previous-key'];
        $plugin = $this->buildPluginMock($settings);
        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->method('secretConfigExists')->willReturn(true);

        $altmetricsClient = $this->createMock(Altmetrics::class);
        $altmetricsClient->expects($this->never())->method('fetchBestScoreSubmissions');

        $form = new TestableRankingCustomizationForm(
            $plugin,
            self::CONTEXT_ID,
            'trending',
            $encryption,
            $altmetricsClient
        );
        $form->setData('customTitle', []);
        $form->setData('description', []);
        $form->setData('itemsPerTab', 4);
        $form->setData('itemsPerPage', 4);
        $form->setData('altmetricsApiKey', '');
        $form->setData('removeAltmetricsApiKey', true);

        $valid = $form->validate();

        $this->assertTrue($valid);
    }

    /**
     * @test
     */
    public function itShouldNotCollectApiKeyFieldsForNonTrendingTabs()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);
        $encryption = $this->createMock(APIKeyEncryption::class);

        $form = new TestableRankingCustomizationForm($plugin, self::CONTEXT_ID, 'mostRead', $encryption);

        $reflection = new ReflectionMethod($form, 'readInputData');
        $vars = $form->getApiKeyUserVars();

        $this->assertNotContains('altmetricsApiKey', $vars);
        $this->assertNotContains('removeAltmetricsApiKey', $vars);
    }

    /**
     * @test
     */
    public function itShouldExposeHasAltmetricsApiKeyFlagWithoutLeakingValue()
    {
        $settings = ['altmetricsApiKey_trending' => 'base64:stored'];
        $plugin = $this->buildPluginMock($settings);
        $encryption = $this->createMock(APIKeyEncryption::class);

        $form = new TestableRankingCustomizationForm($plugin, self::CONTEXT_ID, 'trending', $encryption);
        $form->initData();

        $this->assertTrue($form->getData('hasAltmetricsApiKey'));
        $this->assertNull($form->getData('altmetricsApiKey'));
    }
}
