<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.rankingPlugin.classes.cache.TrendingSubmissions');
import('plugins.generic.rankingPlugin.classes.cache.BestAltmetricsScoreDois');
import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');
import('plugins.generic.rankingPlugin.lib.APIKeyEncryption.APIKeyEncryption');
import('plugins.generic.rankingPlugin.RankingPlugin');

class TrendingSubmissionsTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const CONTEXT_PATH = 'rbgdp';
    private const LIMIT = 4;
    private const JOURNAL_PRINT_ISSN = '2179-7560';

    private const ENCRYPTED_API_KEY = 'base64:encrypted-blob';
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

    private function buildPluginMock(array $settings)
    {
        $plugin = $this->createMock(RankingPlugin::class);
        $plugin->method('getSetting')
            ->willReturnCallback(function ($contextId, $key) use ($settings) {
                return $settings[$key] ?? null;
            });
        return $plugin;
    }

    /**
     * @test
     */
    public function itShouldCallAltmetricApiWithDecryptedKeyWhenKeyIsConfigured()
    {
        $plugin = $this->buildPluginMock([
            'altmetricsApiKey_trending' => self::ENCRYPTED_API_KEY,
        ]);

        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->method('decryptString')
            ->with(self::ENCRYPTED_API_KEY)
            ->willReturn(self::DECRYPTED_API_KEY);

        $bestDois = $this->createMock(BestAltmetricsScoreDois::class);
        $bestDois->expects($this->once())
            ->method('refreshCache')
            ->with(self::CONTEXT_ID, self::JOURNAL_PRINT_ISSN, self::LIMIT, self::DECRYPTED_API_KEY)
            ->willReturn([self::ALTMETRIC_RETURNED_DOI_FIRST, self::ALTMETRIC_RETURNED_DOI_SECOND]);

        $service = $this->createMock(RankingSubmissionService::class);
        $service->method('getBestAltmetricsScoreSubmissions')->willReturn([]);

        $trending = new class ($plugin, $bestDois, $encryption, self::JOURNAL_PRINT_ISSN, $service) extends TrendingSubmissions {
            private $issn;
            private $service;
            public function __construct($plugin, $bestDois, $encryption, $issn, $service)
            {
                parent::__construct($plugin, $bestDois, $encryption);
                $this->issn = $issn;
                $this->service = $service;
            }
            protected function getContextIssn($contextId): ?string
            {
                return $this->issn;
            }
            protected function createRankingSubmissionService($cid, $cp, $l)
            {
                return $this->service;
            }
        };

        $trending->refreshCache(self::CONTEXT_ID, self::CONTEXT_PATH, self::LIMIT);
    }

    /**
     * @test
     */
    public function itShouldUseManualDoisAndSkipAltmetricWhenNoKey()
    {
        $manualDois = [
            self::FIRST_OPTION_ID => self::FIRST_TRENDING_DOI,
            self::SECOND_OPTION_ID => self::SECOND_TRENDING_DOI,
        ];
        $plugin = $this->buildPluginMock([
            'altmetricsApiKey_trending' => '',
            'trendingDois_trending' => $manualDois,
        ]);

        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->expects($this->never())->method('decryptString');

        $bestDois = $this->createMock(BestAltmetricsScoreDois::class);
        $bestDois->expects($this->never())->method('refreshCache');

        $service = $this->createMock(RankingSubmissionService::class);
        $service->expects($this->once())
            ->method('getBestAltmetricsScoreSubmissions')
            ->with([self::FIRST_TRENDING_DOI, self::SECOND_TRENDING_DOI], $this->anything())
            ->willReturn([]);

        $trending = new class ($plugin, $bestDois, $encryption, $service) extends TrendingSubmissions {
            private $service;
            public function __construct($plugin, $bestDois, $encryption, $service)
            {
                parent::__construct($plugin, $bestDois, $encryption);
                $this->service = $service;
            }
            protected function getContextIssn($contextId): ?string
            {
                return null;
            }
            protected function createRankingSubmissionService($cid, $cp, $l)
            {
                return $this->service;
            }
        };

        $trending->refreshCache(self::CONTEXT_ID, self::CONTEXT_PATH, self::LIMIT);
    }

    /**
     * @test
     */
    public function itShouldRespectLimitWhenUsingManualDois()
    {
        $manualDois = [
            self::FIRST_OPTION_ID => self::FIRST_TRENDING_DOI,
            self::SECOND_OPTION_ID => self::SECOND_TRENDING_DOI,
            self::THIRD_OPTION_ID => self::THIRD_TRENDING_DOI,
            self::FOURTH_OPTION_ID => self::FOURTH_TRENDING_DOI,
            self::FIFTH_OPTION_ID => self::FIFTH_TRENDING_DOI,
        ];
        $plugin = $this->buildPluginMock([
            'altmetricsApiKey_trending' => null,
            'trendingDois_trending' => $manualDois,
        ]);

        $service = $this->createMock(RankingSubmissionService::class);
        $service->expects($this->once())
            ->method('getBestAltmetricsScoreSubmissions')
            ->with(
                [self::FIRST_TRENDING_DOI, self::SECOND_TRENDING_DOI, self::THIRD_TRENDING_DOI],
                $this->anything()
            )
            ->willReturn([]);

        $trending = new class ($plugin, null, null, $service) extends TrendingSubmissions {
            private $service;
            public function __construct($plugin, $bestDois, $encryption, $service)
            {
                parent::__construct($plugin, $bestDois, $encryption);
                $this->service = $service;
            }
            protected function getContextIssn($contextId): ?string
            {
                return null;
            }
            protected function createRankingSubmissionService($cid, $cp, $l)
            {
                return $this->service;
            }
        };

        $trending->refreshCache(self::CONTEXT_ID, self::CONTEXT_PATH, 3);
    }
}
