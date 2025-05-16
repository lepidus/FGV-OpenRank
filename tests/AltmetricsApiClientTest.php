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
    private const DOI = 'xxxxxxxxxxxxxxxx';

    /**
     * @test
    */
    public function itShoudReturnServerErrorWhenTryToRetrieveAltmetrics()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ServerException('Server error', new Request('GET', 'https://api.crossref.org/works')));

        $apiClient = new Altmetrics($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.altmetrics.serverError##"
        );
        $statusCode = $apiClient->fetchAltmetrics(self::DOI);
    }

    /**
     * @test
    */
    public function itShouldReturnClientErrorWhenTryToRetrieveAltmetrics()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ClientException('Client error', new Request('GET', 'https://api.crossref.org/works')));

        $apiClient = new Altmetrics($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.altmetrics.clientError##"
        );
        $statusCode = $apiClient->fetchAltmetrics(self::DOI);
    }

    /**
     * @test
    */
    public function itShouldReturnTransferErrorWhenTryToRetrieveAltmetrics()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new TransferException('Transfer error'));

        $apiClient = new Altmetrics($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.altmetrics.transferError##"
        );
        $statusCode = $apiClient->fetchAltmetrics(self::DOI);
    }
}
