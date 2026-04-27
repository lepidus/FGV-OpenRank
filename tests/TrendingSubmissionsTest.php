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
    private const CONTEXT_PATH = 'testjournal';
    private const LIMIT = 4;

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
            'altmetricsApiKey_trending' => 'base64:encrypted-blob',
        ]);

        $encryption = $this->createMock(APIKeyEncryption::class);
        $encryption->method('decryptString')
            ->with('base64:encrypted-blob')
            ->willReturn('decrypted-key');

        $bestDois = $this->createMock(BestAltmetricsScoreDois::class);
        $bestDois->expects($this->once())
            ->method('refreshCache')
            ->with(self::CONTEXT_ID, '1234-5678', self::LIMIT, 'decrypted-key')
            ->willReturn(['10.1234/a', '10.1234/b']);

        $service = $this->createMock(RankingSubmissionService::class);
        $service->method('getBestAltmetricsScoreSubmissions')->willReturn([]);

        $trending = new class($plugin, $bestDois, $encryption, '1234-5678', $service) extends TrendingSubmissions {
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
        $manualDois = ['uid1' => '10.1234/a', 'uid2' => '10.5678/b'];
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
            ->with(['10.1234/a', '10.5678/b'], $this->anything())
            ->willReturn([]);

        $trending = new class($plugin, $bestDois, $encryption, $service) extends TrendingSubmissions {
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
            'u1' => '10.1/a', 'u2' => '10.1/b', 'u3' => '10.1/c',
            'u4' => '10.1/d', 'u5' => '10.1/e',
        ];
        $plugin = $this->buildPluginMock([
            'altmetricsApiKey_trending' => null,
            'trendingDois_trending' => $manualDois,
        ]);

        $service = $this->createMock(RankingSubmissionService::class);
        $service->expects($this->once())
            ->method('getBestAltmetricsScoreSubmissions')
            ->with(['10.1/a', '10.1/b', '10.1/c'], $this->anything())
            ->willReturn([]);

        $trending = new class($plugin, null, null, $service) extends TrendingSubmissions {
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
