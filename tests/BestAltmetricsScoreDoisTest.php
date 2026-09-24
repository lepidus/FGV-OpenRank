<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\cache\BestAltmetricsScoreDois;
use APP\plugins\generic\rankingPlugin\classes\clients\Altmetrics;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class BestAltmetricsScoreDoisTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const ISSN = '1234-5678';
    private const LIMIT = 4;

    #[Test]
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

    #[Test]
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
