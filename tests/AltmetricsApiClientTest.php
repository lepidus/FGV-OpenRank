<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.rankingPlugin.classes.clients.Altmetrics');
import('plugins.generic.rankingPlugin.tests.helpers.ClientInterfaceForTests');

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;

class AltmetricsApiClientTest extends PKPTestCase
{
    private const ISSN = '1234-5678';
    private const LIMIT = 4;

    /**
     * @test
     */
    public function itShouldReturnServerErrorWhenTryToRetrieveBestScoreSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ServerException('Server error', new Request('GET', 'https://api.altmetric.com/v1/citations/at')));

        $apiClient = new Altmetrics($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            __("plugins.generic.rankingPlugin.client.altmetrics.serverError")
        );
        $apiClient->fetchBestScoreSubmissions(self::ISSN, self::LIMIT);
    }

    /**
     * @test
     */
    public function itShouldReturnClientErrorWhenTryToRetrieveBestScoreSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ClientException('Client error', new Request('GET', 'https://api.altmetric.com/v1/citations/at')));

        $apiClient = new Altmetrics($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            __("plugins.generic.rankingPlugin.client.altmetrics.clientError")
        );
        $apiClient->fetchBestScoreSubmissions(self::ISSN, self::LIMIT);
    }

    /**
     * @test
     */
    public function itShouldReturnTransferErrorWhenTryToRetrieveBestScoreSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new TransferException('Transfer error'));

        $apiClient = new Altmetrics($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            __("plugins.generic.rankingPlugin.client.altmetrics.transferError")
        );
        $apiClient->fetchBestScoreSubmissions(self::ISSN, self::LIMIT);
    }
}
