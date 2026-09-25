<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\cache\BestAltmetricsScoreDois;
use APP\plugins\generic\rankingPlugin\classes\cache\TrendingSubmissions;
use APP\plugins\generic\rankingPlugin\classes\factory\RankingSubmission;
use APP\plugins\generic\rankingPlugin\classes\settings\AltmetricsApiKey;
use APP\plugins\generic\rankingPlugin\classes\settings\TrendingDois;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class TrendingSubmissionsTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const LIMIT = 4;
    private const JOURNAL_PRINT_ISSN = '2179-7560';

    private const DECRYPTED_API_KEY = 'altmetric-api-key-plaintext';

    private const FIRST_OPTION_ID = '64f1a0b7c2d31';
    private const SECOND_OPTION_ID = '64f1a0b7c2d32';
    private const THIRD_OPTION_ID = '64f1a0b7c2d33';
    private const FOURTH_OPTION_ID = '64f1a0b7c2d34';
    private const FIFTH_OPTION_ID = '64f1a0b7c2d35';

    private const FIRST_TRENDING_DOI = '10.4322/2179-7560.2024.001';
    private const SECOND_TRENDING_DOI = '10.4322/2179-7560.2024.002';
    private const THIRD_TRENDING_DOI = '10.4322/2179-7560.2024.003';
    private const FOURTH_TRENDING_DOI = '10.4322/2179-7560.2024.004';
    private const FIFTH_TRENDING_DOI = '10.4322/2179-7560.2024.005';

    private const ALTMETRIC_RETURNED_DOI_FIRST = '10.4322/2179-7560.2023.011';
    private const ALTMETRIC_RETURNED_DOI_SECOND = '10.4322/2179-7560.2023.012';

    private function buildApiKeyMock(?string $apiKey): AltmetricsApiKey
    {
        $altmetricsApiKey = $this->createMock(AltmetricsApiKey::class);
        $altmetricsApiKey->method('get')->willReturn($apiKey);
        return $altmetricsApiKey;
    }

    private function buildTrendingDoisMock(array $storedDois): TrendingDois
    {
        $trendingDois = $this->createMock(TrendingDois::class);
        $trendingDois->method('getStored')->willReturn($storedDois);
        return $trendingDois;
    }

    #[Test]
    public function itShouldCallAltmetricApiWithDecryptedKeyWhenKeyIsConfigured()
    {
        $altmetricDois = [self::ALTMETRIC_RETURNED_DOI_FIRST, self::ALTMETRIC_RETURNED_DOI_SECOND];

        $bestDois = $this->createMock(BestAltmetricsScoreDois::class);
        $bestDois->expects($this->once())
            ->method('refreshCache')
            ->with(self::CONTEXT_ID, self::JOURNAL_PRINT_ISSN, self::LIMIT, self::DECRYPTED_API_KEY)
            ->willReturn($altmetricDois);

        $rankingSubmission = $this->createMock(RankingSubmission::class);
        $rankingSubmission->expects($this->once())
            ->method('getTrending')
            ->with($altmetricDois)
            ->willReturn([]);

        (new TrendingSubmissions(
            self::CONTEXT_ID,
            self::JOURNAL_PRINT_ISSN,
            $this->buildApiKeyMock(self::DECRYPTED_API_KEY),
            $this->buildTrendingDoisMock([]),
            $rankingSubmission,
            $bestDois
        ))->refreshCache(self::LIMIT);
    }

    #[Test]
    public function itShouldUseManualDoisAndSkipAltmetricWhenNoKey()
    {
        $bestDois = $this->createMock(BestAltmetricsScoreDois::class);
        $bestDois->expects($this->never())->method('refreshCache');

        $rankingSubmission = $this->createMock(RankingSubmission::class);
        $rankingSubmission->expects($this->once())
            ->method('getTrending')
            ->with([self::FIRST_TRENDING_DOI, self::SECOND_TRENDING_DOI])
            ->willReturn([]);

        (new TrendingSubmissions(
            self::CONTEXT_ID,
            null,
            $this->buildApiKeyMock(null),
            $this->buildTrendingDoisMock([
                self::FIRST_OPTION_ID => self::FIRST_TRENDING_DOI,
                self::SECOND_OPTION_ID => self::SECOND_TRENDING_DOI,
            ]),
            $rankingSubmission,
            $bestDois
        ))->refreshCache(self::LIMIT);
    }

    #[Test]
    public function itShouldNotCallAltmetricWhenTheJournalHasNoIssn()
    {
        $bestDois = $this->createMock(BestAltmetricsScoreDois::class);
        $bestDois->expects($this->never())->method('refreshCache');

        $rankingSubmission = $this->createMock(RankingSubmission::class);
        $rankingSubmission->expects($this->never())->method('getTrending');

        $trendingSubmissions = (new TrendingSubmissions(
            self::CONTEXT_ID,
            null,
            $this->buildApiKeyMock(self::DECRYPTED_API_KEY),
            $this->buildTrendingDoisMock([self::FIRST_OPTION_ID => self::FIRST_TRENDING_DOI]),
            $rankingSubmission,
            $bestDois
        ))->refreshCache(self::LIMIT);

        $this->assertSame([], $trendingSubmissions);
    }

    #[Test]
    public function itShouldRespectLimitWhenUsingManualDois()
    {
        $rankingSubmission = $this->createMock(RankingSubmission::class);
        $rankingSubmission->expects($this->once())
            ->method('getTrending')
            ->with([self::FIRST_TRENDING_DOI, self::SECOND_TRENDING_DOI, self::THIRD_TRENDING_DOI])
            ->willReturn([]);

        (new TrendingSubmissions(
            self::CONTEXT_ID,
            null,
            $this->buildApiKeyMock(null),
            $this->buildTrendingDoisMock([
                self::FIRST_OPTION_ID => self::FIRST_TRENDING_DOI,
                self::SECOND_OPTION_ID => self::SECOND_TRENDING_DOI,
                self::THIRD_OPTION_ID => self::THIRD_TRENDING_DOI,
                self::FOURTH_OPTION_ID => self::FOURTH_TRENDING_DOI,
                self::FIFTH_OPTION_ID => self::FIFTH_TRENDING_DOI,
            ]),
            $rankingSubmission
        ))->refreshCache(3);
    }
}
