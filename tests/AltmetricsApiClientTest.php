<?php

namespace APP\plugins\generic\fgvOpenRank\tests;

use APP\plugins\generic\fgvOpenRank\classes\clients\Altmetrics;
use APP\plugins\generic\fgvOpenRank\tests\helpers\ClientInterfaceForTests;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\InvalidArgumentException;
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
    private const API_KEY = 'secret-altmetric-key';
    private const URI_WITH_KEY = 'https://api.altmetric.com/v1/citations/at?num_results=4&issns=1234-5678&order_by=score&key=secret-altmetric-key';

    #[Test]
    public function itShouldReturnServerErrorWhenTryToRetrieveBestScoreSubmissions()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new ServerException('Server error', new Request('GET', 'https://api.altmetric.com/v1/citations/at'), new Response(500)));

        $apiClient = new Altmetrics($httpClientMock);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            __("plugins.generic.fgvOpenRank.client.altmetrics.serverError")
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
            __("plugins.generic.fgvOpenRank.client.altmetrics.clientError")
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
            __("plugins.generic.fgvOpenRank.client.altmetrics.transferError")
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

    private function fetchAndCaptureLog($httpClientMock): array
    {
        $logFile = tempnam(sys_get_temp_dir(), 'fgvOpenRankLog');
        $previousLog = ini_set('error_log', $logFile);
        $message = null;

        try {
            (new Altmetrics($httpClientMock))->fetchBestScoreSubmissions(self::ISSN, self::LIMIT, self::API_KEY);
        } catch (Exception $error) {
            $message = $error->getMessage();
        } finally {
            ini_set('error_log', $previousLog);
            $log = file_get_contents($logFile);
            unlink($logFile);
        }

        return [$message, $log];
    }

    #[Test]
    public function itShouldNotWriteTheApiKeyToTheLogWhenTheRequestFails()
    {
        $request = new Request('GET', self::URI_WITH_KEY);
        $failures = [
            new ClientException('Client error: `GET ' . self::URI_WITH_KEY . '` resulted in a `403 Forbidden` response', $request, new Response(403)),
            new ServerException('Server error: `GET ' . self::URI_WITH_KEY . '` resulted in a `502 Bad Gateway` response', $request, new Response(502)),
            new TransferException('cURL error 28: Operation timed out for ' . self::URI_WITH_KEY),
        ];

        foreach ($failures as $failure) {
            $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
            $httpClientMock->method('request')->willThrowException($failure);

            [$message, $log] = $this->fetchAndCaptureLog($httpClientMock);

            $this->assertNotNull($message);
            $this->assertNotSame('', $log, get_class($failure));
            $this->assertStringNotContainsString(self::API_KEY, $log, get_class($failure));
            $this->assertStringNotContainsString(self::API_KEY, $message, get_class($failure));
        }
    }

    #[Test]
    public function itShouldNotPutTheApiKeyInTheMessageOfOtherGuzzleErrors()
    {
        $httpClientMock = $this->createMock(ClientInterfaceForTests::class);
        $httpClientMock->method('request')
            ->willThrowException(new InvalidArgumentException('Invalid URI ' . self::URI_WITH_KEY));

        [$message, $log] = $this->fetchAndCaptureLog($httpClientMock);

        $this->assertStringStartsWith('Altmetrics Error: ', $message);
        $this->assertStringNotContainsString(self::API_KEY, $message);
        $this->assertStringNotContainsString(self::API_KEY, $log);
    }
}
