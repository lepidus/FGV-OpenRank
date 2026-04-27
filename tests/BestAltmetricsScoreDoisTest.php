<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.rankingPlugin.classes.cache.BestAltmetricsScoreDois');
import('plugins.generic.rankingPlugin.classes.clients.Altmetrics');

class BestAltmetricsScoreDoisTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const ISSN = '1234-5678';
    private const LIMIT = 4;

    /**
     * @test
     */
    public function itShouldPassApiKeyToAltmetricsClient()
    {
        $altmetricsMock = $this->createMock(Altmetrics::class);
        $altmetricsMock->expects($this->once())
            ->method('fetchBestScoreSubmissions')
            ->with(self::ISSN, self::LIMIT, 'my-api-key')
            ->willReturn(['results' => []]);

        $bestDois = new BestAltmetricsScoreDois($altmetricsMock);
        $bestDois->refreshCache(self::CONTEXT_ID, self::ISSN, self::LIMIT, 'my-api-key');
    }

    /**
     * @test
     */
    public function itShouldPassNullWhenNoApiKeyProvided()
    {
        $altmetricsMock = $this->createMock(Altmetrics::class);
        $altmetricsMock->expects($this->once())
            ->method('fetchBestScoreSubmissions')
            ->with(self::ISSN, self::LIMIT, null)
            ->willReturn(['results' => []]);

        $bestDois = new BestAltmetricsScoreDois($altmetricsMock);
        $bestDois->refreshCache(self::CONTEXT_ID, self::ISSN, self::LIMIT);
    }
}
