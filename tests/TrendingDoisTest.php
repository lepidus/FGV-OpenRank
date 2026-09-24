<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\settings\TrendingDois;
use APP\plugins\generic\rankingPlugin\RankingPlugin;
use APP\submission\Repository as SubmissionRepository;
use APP\submission\Submission;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class TrendingDoisTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;

    private const FIRST_OPTION_ID = '64f1a0b7c2d31';
    private const SECOND_OPTION_ID = '64f1a0b7c2d32';
    private const THIRD_OPTION_ID = '64f1a0b7c2d33';
    private const PREVIOUS_OPTION_ID = '64eeeeeebbbb1';
    private const ABSENT_OPTION_ID = '64ffffffffff9';

    private const FIRST_DOI = '10.4322/2179-7560.2024.001';
    private const SECOND_DOI = '10.4322/2179-7560.2024.002';
    private const SECOND_DOI_AFTER_EDIT = '10.4322/2179-7560.2024.002-corrigendum';
    private const THIRD_DOI = '10.4322/2179-7560.2024.003';
    private const PREVIOUS_JOURNAL_DOI = '10.4322/2179-7560.2023.012';
    private const NEW_DOI = '10.4322/2179-7560.2024.004';
    private const DOI_ABSENT_FROM_JOURNAL = '10.1234/article-from-another-journal';

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

    private function buildSubmissionRepositoryMock(?Submission $returnValue)
    {
        $submissionRepository = $this->createMock(SubmissionRepository::class);
        $submissionRepository->method('getByDoi')->willReturn($returnValue);
        return $submissionRepository;
    }

    private function buildTrendingDois(array &$settings, $submissionRepository = null): TrendingDois
    {
        return new TrendingDois($this->buildPluginMock($settings), self::CONTEXT_ID, $submissionRepository);
    }

    #[Test]
    public function itShouldRejectInvalidDoi()
    {
        $settings = [];
        $trendingDois = $this->buildTrendingDois($settings, $this->buildSubmissionRepositoryMock(new Submission()));

        $this->assertSame(
            __('plugins.generic.rankingPlugin.trendingDois.invalidDoi'),
            $trendingDois->validate('not-a-doi')
        );
    }

    #[Test]
    public function itShouldAcceptValidDoi()
    {
        $settings = [];
        $trendingDois = $this->buildTrendingDois($settings, $this->buildSubmissionRepositoryMock(new Submission()));

        $this->assertNull($trendingDois->validate(self::FIRST_DOI));
    }

    #[Test]
    public function itShouldRejectDoiNotPresentInJournal()
    {
        $settings = [];
        $trendingDois = $this->buildTrendingDois($settings, $this->buildSubmissionRepositoryMock(null));

        $this->assertSame(
            __('plugins.generic.rankingPlugin.trendingDois.doiNotInJournal'),
            $trendingDois->validate(self::DOI_ABSENT_FROM_JOURNAL)
        );
    }

    #[Test]
    public function itShouldLookupDoiUsingDoiAndContextId()
    {
        $settings = [];
        $submissionRepository = $this->createMock(SubmissionRepository::class);
        $submissionRepository->expects($this->once())
            ->method('getByDoi')
            ->with(self::FIRST_DOI, self::CONTEXT_ID)
            ->willReturn(new Submission());

        $trendingDois = $this->buildTrendingDois($settings, $submissionRepository);

        $this->assertNull($trendingDois->validate(self::FIRST_DOI));
    }

    #[Test]
    public function itShouldNotLookupDoiInJournalWhenFormatIsInvalid()
    {
        $settings = [];
        $submissionRepository = $this->createMock(SubmissionRepository::class);
        $submissionRepository->expects($this->never())->method('getByDoi');

        $trendingDois = $this->buildTrendingDois($settings, $submissionRepository);

        $this->assertNotNull($trendingDois->validate('not-a-doi'));
    }

    #[Test]
    public function itShouldInsertNewDoiWithGeneratedId()
    {
        $settings = [];
        $optionId = $this->buildTrendingDois($settings)->save(self::FIRST_DOI);

        $this->assertIsString($optionId);
        $this->assertSame([self::FIRST_DOI], array_values($settings[TrendingDois::SETTING_NAME]));
        $this->assertSame($optionId, array_keys($settings[TrendingDois::SETTING_NAME])[0]);
    }

    #[Test]
    public function itShouldAppendDoiPreservingExisting()
    {
        $settings = [
            TrendingDois::SETTING_NAME => [self::PREVIOUS_OPTION_ID => self::PREVIOUS_JOURNAL_DOI],
        ];
        $this->buildTrendingDois($settings)->save(self::NEW_DOI);

        $this->assertSame(
            [self::PREVIOUS_JOURNAL_DOI, self::NEW_DOI],
            array_values($settings[TrendingDois::SETTING_NAME])
        );
        $this->assertArrayHasKey(self::PREVIOUS_OPTION_ID, $settings[TrendingDois::SETTING_NAME]);
    }

    #[Test]
    public function itShouldEditExistingDoiPreservingKey()
    {
        $settings = [
            TrendingDois::SETTING_NAME => [
                self::FIRST_OPTION_ID => self::FIRST_DOI,
                self::SECOND_OPTION_ID => self::SECOND_DOI,
                self::THIRD_OPTION_ID => self::THIRD_DOI,
            ],
        ];
        $this->buildTrendingDois($settings)->save(self::SECOND_DOI_AFTER_EDIT, self::SECOND_OPTION_ID);

        $this->assertSame(
            [
                self::FIRST_OPTION_ID => self::FIRST_DOI,
                self::SECOND_OPTION_ID => self::SECOND_DOI_AFTER_EDIT,
                self::THIRD_OPTION_ID => self::THIRD_DOI,
            ],
            $settings[TrendingDois::SETTING_NAME]
        );
    }

    #[Test]
    public function itShouldListStoredDoisInTheirOrder()
    {
        $settings = [
            TrendingDois::SETTING_NAME => [
                self::FIRST_OPTION_ID => self::FIRST_DOI,
                self::SECOND_OPTION_ID => self::SECOND_DOI,
            ],
        ];

        $this->assertSame([
            ['id' => self::FIRST_OPTION_ID, 'doi' => self::FIRST_DOI],
            ['id' => self::SECOND_OPTION_ID, 'doi' => self::SECOND_DOI],
        ], $this->buildTrendingDois($settings)->getItems());
    }

    #[Test]
    public function itShouldReturnEmptyListWhenNoDoisStored()
    {
        $settings = [];

        $this->assertSame([], $this->buildTrendingDois($settings)->getItems());
    }

    #[Test]
    public function itShouldReorderDoisAccordingToProvidedOrder()
    {
        $settings = [
            TrendingDois::SETTING_NAME => [
                self::FIRST_OPTION_ID => self::FIRST_DOI,
                self::SECOND_OPTION_ID => self::SECOND_DOI,
                self::THIRD_OPTION_ID => self::THIRD_DOI,
            ],
        ];
        $this->buildTrendingDois($settings)->reorder([self::THIRD_OPTION_ID, self::FIRST_OPTION_ID, self::SECOND_OPTION_ID]);

        $this->assertSame(
            [self::THIRD_OPTION_ID, self::FIRST_OPTION_ID, self::SECOND_OPTION_ID],
            array_keys($settings[TrendingDois::SETTING_NAME])
        );
        $this->assertSame(
            [self::THIRD_DOI, self::FIRST_DOI, self::SECOND_DOI],
            array_values($settings[TrendingDois::SETTING_NAME])
        );
    }

    #[Test]
    public function itShouldPreserveUnlistedDoisAtTheEndWhenReordering()
    {
        $result = TrendingDois::reorderDois(
            [
                self::FIRST_OPTION_ID => self::FIRST_DOI,
                self::SECOND_OPTION_ID => self::SECOND_DOI,
                self::THIRD_OPTION_ID => self::THIRD_DOI,
            ],
            [self::THIRD_OPTION_ID]
        );

        $this->assertSame(
            [self::THIRD_OPTION_ID, self::FIRST_OPTION_ID, self::SECOND_OPTION_ID],
            array_keys($result)
        );
    }

    #[Test]
    public function itShouldRemoveDoiByOptionId()
    {
        $settings = [
            TrendingDois::SETTING_NAME => [
                self::FIRST_OPTION_ID => self::FIRST_DOI,
                self::SECOND_OPTION_ID => self::SECOND_DOI,
                self::THIRD_OPTION_ID => self::THIRD_DOI,
            ],
        ];
        $this->buildTrendingDois($settings)->remove(self::SECOND_OPTION_ID);

        $this->assertSame([
            self::FIRST_OPTION_ID => self::FIRST_DOI,
            self::THIRD_OPTION_ID => self::THIRD_DOI,
        ], $settings[TrendingDois::SETTING_NAME]);
    }

    #[Test]
    public function itShouldReturnUnchangedWhenRemovingNonexistentDoi()
    {
        $stored = [self::FIRST_OPTION_ID => self::FIRST_DOI];

        $this->assertSame($stored, TrendingDois::removeDoi($stored, self::ABSENT_OPTION_ID));
    }
}
