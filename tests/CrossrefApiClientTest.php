<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.rankingPlugin.classes.clients.Crossref');
import('plugins.generic.rankingPlugin.tests.helpers.ClientInterfaceForTests');

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;

class CrossrefApiClientTest extends PKPTestCase
{
    private const ISSN = '1234-5678';
    private const LIMIT = 4;

    /**
     * @test
    */
    public function itShoudReturnServerErrorWhenTryToRetrieveMostCitedSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ServerException('Server error', new Request('POST', 'https://api.crossref.org/')));

        $apiClient = new Crossref($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.serverError##"
        );
        $statusCode = $apiClient->getMostCitedSubmissions(self::ISSN, self::LIMIT);
    }
}
