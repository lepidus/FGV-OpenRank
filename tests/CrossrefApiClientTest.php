<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\clients\Crossref;
use APP\plugins\generic\rankingPlugin\tests\helpers\ClientInterfaceForTests;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class CrossrefApiClientTest extends PKPTestCase
{
    private const ISSN = '1234-5678';
    private const LIMIT = 4;

    #[Test]
    public function itShoudReturnServerErrorWhenTryToRetrieveMostCitedSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ServerException('Server error', new Request('GET', 'https://api.crossref.org/works'), new Response(500)));

        $apiClient = new Crossref($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.crossref.serverError##"
        );
        $statusCode = $apiClient->fetchMostCitedSubmissions(self::ISSN, self::LIMIT);
    }

    #[Test]
    public function itShoudReturnClientErrorWhenTryToRetrieveMostCitedSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ClientException('Client error', new Request('GET', 'https://api.crossref.org/works'), new Response(400)));

        $apiClient = new Crossref($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.crossref.clientError##"
        );
        $statusCode = $apiClient->fetchMostCitedSubmissions(self::ISSN, self::LIMIT);
    }

    #[Test]
    public function itShoudReturnTransferErrorWhenTryToRetrieveMostCitedSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new TransferException('Transfer error'));

        $apiClient = new Crossref($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "##plugins.generic.rankingPlugin.client.crossref.transferError##"
        );
        $statusCode = $apiClient->fetchMostCitedSubmissions(self::ISSN, self::LIMIT);
    }
}
