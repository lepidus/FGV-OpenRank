<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.rankingPlugin.classes.clients.Crossref');
import('plugins.generic.rankingPlugin.tests.helpers.ClientInterfaceForTests');

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
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
            ->willThrowException(new ServerException('Server error', new Request('GET', 'https://api.crossref.org/works')));

        $apiClient = new Crossref($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.serverError##"
        );
        $statusCode = $apiClient->fetchMostCitedSubmissions(self::ISSN, self::LIMIT);
    }

    /**
     * @test
    */
    public function itShoudReturnClientErrorWhenTryToRetrieveMostCitedSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ClientException('Client error', new Request('GET', 'https://api.crossref.org/works')));

        $apiClient = new Crossref($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.clientError##"
        );
        $statusCode = $apiClient->fetchMostCitedSubmissions(self::ISSN, self::LIMIT);
    }

    /**
     * @test
    */
    public function itShoudReturnTransferErrorWhenTryToRetrieveMostCitedSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new TransferException('Transfer error'));

        $apiClient = new Crossref($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.transferError##"
        );
        $statusCode = $apiClient->fetchMostCitedSubmissions(self::ISSN, self::LIMIT);
    }
}
