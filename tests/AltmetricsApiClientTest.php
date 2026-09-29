<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\clients\Altmetrics;
use APP\plugins\generic\rankingPlugin\tests\helpers\ClientInterfaceForTests;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class AltmetricsApiClientTest extends PKPTestCase
{
    private const ISSN = '1234-5678';
    private const LIMIT = 4;

    #[Test]
    public function itShouldReturnServerErrorWhenTryToRetrieveBestScoreSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ServerException('Server error', new Request('GET', 'https://api.altmetric.com/v1/citations/at'), new Response(500)));

        $apiClient = new Altmetrics($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            __("plugins.generic.rankingPlugin.client.altmetrics.serverError")
        );
        $apiClient->fetchBestScoreSubmissions(self::ISSN, self::LIMIT);
    }

    #[Test]
    public function itShouldReturnClientErrorWhenTryToRetrieveBestScoreSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ClientException('Client error', new Request('GET', 'https://api.altmetric.com/v1/citations/at'), new Response(400)));

        $apiClient = new Altmetrics($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            __("plugins.generic.rankingPlugin.client.altmetrics.clientError")
        );
        $apiClient->fetchBestScoreSubmissions(self::ISSN, self::LIMIT);
    }

    #[Test]
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

    #[Test]
    public function itShouldSendApiKeyWhenProvided()
    {
        $response = new Response(200, [], json_encode(['results' => []]));
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->anything(),
                $this->callback(function ($options) {
                    return isset($options['query'])
                        && ($options['query']['key'] ?? null) === 'xyz'
                        && ($options['query']['num_results'] ?? null) === self::LIMIT
                        && ($options['query']['issns'] ?? null) === self::ISSN
                        && ($options['query']['order_by'] ?? null) === 'score';
                })
            )
            ->willReturn($response);

        $apiClient = new Altmetrics($httpClientMock);
        $apiClient->fetchBestScoreSubmissions(self::ISSN, self::LIMIT, 'xyz');
    }

    #[Test]
    public function itShouldOmitApiKeyWhenNotProvided()
    {
        $response = new Response(200, [], json_encode(['results' => []]));
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->anything(),
                $this->callback(function ($options) {
                    return isset($options['query'])
                        && !array_key_exists('key', $options['query']);
                })
            )
            ->willReturn($response);

        $apiClient = new Altmetrics($httpClientMock);
        $apiClient->fetchBestScoreSubmissions(self::ISSN, self::LIMIT);
    }
}
