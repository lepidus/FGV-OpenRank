<?php

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.submission.PKPSubmissionDAO');
import('plugins.generic.rankingPlugin.controllers.grid.form.TrendingDoiForm');
import('plugins.generic.rankingPlugin.RankingPlugin');

class TestableTrendingDoiForm extends TrendingDoiForm
{
    protected function addSecurityValidators()
    {
    }
}

class TrendingDoiFormTest extends PKPTestCase
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

    private function buildSubmissionDaoMock($returnValue)
    {
        $submissionDao = $this->getMockBuilder(PKPSubmissionDAO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByPubId'])
            ->getMockForAbstractClass();
        $submissionDao->method('getByPubId')->willReturn($returnValue);
        return $submissionDao;
    }

    /**
     * @test
     */
    public function itShouldRejectInvalidDoi()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID);
        $form->setData('doi', 'not-a-doi');

        $this->assertFalse($form->validate());
        $errors = $form->getErrorsArray();
        $this->assertArrayHasKey('doi', $errors);
    }

    /**
     * @test
     */
    public function itShouldAcceptValidDoi()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);
        $submissionDao = $this->buildSubmissionDaoMock(new stdClass());

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID, null, $submissionDao);
        $form->setData('doi', '10.1234/example.abc');

        $this->assertTrue($form->validate());
    }

    /**
     * @test
     */
    public function itShouldRejectDoiNotPresentInJournal()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);
        $submissionDao = $this->buildSubmissionDaoMock(null);

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID, null, $submissionDao);
        $form->setData('doi', '10.1234/missing.in.journal');

        $this->assertFalse($form->validate());
        $errors = $form->getErrorsArray();
        $this->assertArrayHasKey('doi', $errors);
    }

    /**
     * @test
     */
    public function itShouldLookupDoiUsingPubIdAndContextId()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);
        $submissionDao = $this->getMockBuilder(PKPSubmissionDAO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByPubId'])
            ->getMockForAbstractClass();
        $submissionDao->expects($this->once())
            ->method('getByPubId')
            ->with('doi', '10.1234/lookup.test', self::CONTEXT_ID)
            ->willReturn(new stdClass());

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID, null, $submissionDao);
        $form->setData('doi', '10.1234/lookup.test');

        $this->assertTrue($form->validate());
    }

    /**
     * @test
     */
    public function itShouldNotLookupDoiInJournalWhenFormatIsInvalid()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);
        $submissionDao = $this->getMockBuilder(PKPSubmissionDAO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByPubId'])
            ->getMockForAbstractClass();
        $submissionDao->expects($this->never())->method('getByPubId');

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID, null, $submissionDao);
        $form->setData('doi', 'not-a-doi');

        $this->assertFalse($form->validate());
    }

    /**
     * @test
     */
    public function itShouldInsertNewDoiWithGeneratedId()
    {
        $settings = [];
        $plugin = $this->buildPluginMock($settings);

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID);
        $form->setData('doi', '10.1234/first.doi');
        $optionId = $form->execute();

        $this->assertIsString($optionId);
        $this->assertArrayHasKey('trendingDois_trending', $settings);
        $this->assertSame(['10.1234/first.doi'], array_values($settings['trendingDois_trending']));
        $this->assertSame($optionId, array_keys($settings['trendingDois_trending'])[0]);
    }

    /**
     * @test
     */
    public function itShouldAppendDoiPreservingExisting()
    {
        $settings = [
            'trendingDois_trending' => ['existingUid' => '10.9999/old.doi'],
        ];
        $plugin = $this->buildPluginMock($settings);

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID);
        $form->setData('doi', '10.1234/new.doi');
        $form->execute();

        $this->assertSame(
            ['10.9999/old.doi', '10.1234/new.doi'],
            array_values($settings['trendingDois_trending'])
        );
        $this->assertArrayHasKey('existingUid', $settings['trendingDois_trending']);
    }

    /**
     * @test
     */
    public function itShouldEditExistingDoiPreservingKey()
    {
        $settings = [
            'trendingDois_trending' => [
                'uidA' => '10.1/a',
                'uidB' => '10.1/old-b',
                'uidC' => '10.1/c',
            ],
        ];
        $plugin = $this->buildPluginMock($settings);

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID, 'uidB');
        $form->setData('doi', '10.1/new-b');
        $form->execute();

        $this->assertSame(
            ['uidA' => '10.1/a', 'uidB' => '10.1/new-b', 'uidC' => '10.1/c'],
            $settings['trendingDois_trending']
        );
    }

    /**
     * @test
     */
    public function itShouldLoadDoiValueWhenEditing()
    {
        $settings = [
            'trendingDois_trending' => ['uidA' => '10.1/existing'],
        ];
        $plugin = $this->buildPluginMock($settings);

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID, 'uidA');
        $form->initData();

        $this->assertSame('10.1/existing', $form->getData('doi'));
    }
}
