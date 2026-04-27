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

    private const FIRST_OPTION_ID = '64f1a0b7c2d31';
    private const SECOND_OPTION_ID = '64f1a0b7c2d32';
    private const THIRD_OPTION_ID = '64f1a0b7c2d33';
    private const PREVIOUS_OPTION_ID = '64eeeeeebbbb1';

    private const FIRST_DOI = '10.4322/2179-7560.2024.001';
    private const SECOND_DOI_BEFORE_EDIT = '10.4322/2179-7560.2024.002';
    private const SECOND_DOI_AFTER_EDIT = '10.4322/2179-7560.2024.002-corrigendum';
    private const THIRD_DOI = '10.4322/2179-7560.2024.003';
    private const PREVIOUS_JOURNAL_DOI = '10.4322/2179-7560.2023.012';
    private const NEW_DOI = '10.4322/2179-7560.2024.004';
    private const DOI_ABSENT_FROM_JOURNAL = '10.1234/article-from-another-journal';
    private const LOOKUP_DOI = '10.4322/2179-7560.2024.lookup';

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
        $form->setData('doi', self::FIRST_DOI);

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
        $form->setData('doi', self::DOI_ABSENT_FROM_JOURNAL);

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
            ->with('doi', self::LOOKUP_DOI, self::CONTEXT_ID)
            ->willReturn(new stdClass());

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID, null, $submissionDao);
        $form->setData('doi', self::LOOKUP_DOI);

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
        $form->setData('doi', self::FIRST_DOI);
        $optionId = $form->execute();

        $this->assertIsString($optionId);
        $this->assertArrayHasKey('trendingDois_trending', $settings);
        $this->assertSame([self::FIRST_DOI], array_values($settings['trendingDois_trending']));
        $this->assertSame($optionId, array_keys($settings['trendingDois_trending'])[0]);
    }

    /**
     * @test
     */
    public function itShouldAppendDoiPreservingExisting()
    {
        $settings = [
            'trendingDois_trending' => [self::PREVIOUS_OPTION_ID => self::PREVIOUS_JOURNAL_DOI],
        ];
        $plugin = $this->buildPluginMock($settings);

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID);
        $form->setData('doi', self::NEW_DOI);
        $form->execute();

        $this->assertSame(
            [self::PREVIOUS_JOURNAL_DOI, self::NEW_DOI],
            array_values($settings['trendingDois_trending'])
        );
        $this->assertArrayHasKey(self::PREVIOUS_OPTION_ID, $settings['trendingDois_trending']);
    }

    /**
     * @test
     */
    public function itShouldEditExistingDoiPreservingKey()
    {
        $settings = [
            'trendingDois_trending' => [
                self::FIRST_OPTION_ID => self::FIRST_DOI,
                self::SECOND_OPTION_ID => self::SECOND_DOI_BEFORE_EDIT,
                self::THIRD_OPTION_ID => self::THIRD_DOI,
            ],
        ];
        $plugin = $this->buildPluginMock($settings);

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID, self::SECOND_OPTION_ID);
        $form->setData('doi', self::SECOND_DOI_AFTER_EDIT);
        $form->execute();

        $this->assertSame(
            [
                self::FIRST_OPTION_ID => self::FIRST_DOI,
                self::SECOND_OPTION_ID => self::SECOND_DOI_AFTER_EDIT,
                self::THIRD_OPTION_ID => self::THIRD_DOI,
            ],
            $settings['trendingDois_trending']
        );
    }

    /**
     * @test
     */
    public function itShouldLoadDoiValueWhenEditing()
    {
        $settings = [
            'trendingDois_trending' => [self::FIRST_OPTION_ID => self::FIRST_DOI],
        ];
        $plugin = $this->buildPluginMock($settings);

        $form = new TestableTrendingDoiForm($plugin, self::CONTEXT_ID, self::FIRST_OPTION_ID);
        $form->initData();

        $this->assertSame(self::FIRST_DOI, $form->getData('doi'));
    }
}
