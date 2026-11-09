<?php

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;

class Altmetrics
{
    private const BASE_URL = 'https://api.altmetric.com/v1/';
    private const CITATIONS_ENDPOINT = 'citations/at';
    private $_httpClient;

    public function __construct($httpClient)
    {
        $this->_httpClient = $httpClient;
    }


    public function fetchBestScoreSubmissions(string $issn, int $limit, ?string $apiKey = null): array
    {
        $uri = self::BASE_URL . self::CITATIONS_ENDPOINT;
        $query = [
            'num_results' => $limit,
            'issns' => $issn,
            'order_by' => 'score'
        ];
        if ($apiKey !== null && $apiKey !== '') {
            $query['key'] = $apiKey;
        }
        try {
            $response = $this->_httpClient->request(
                'GET',
                $uri,
                ['query' => $query]
            );
            return json_decode($response->getBody()->getContents(), true);
        } catch (ServerException $error) {
            error_log($this->describeFailure($error));
            throw new \Exception(
                __("plugins.generic.fgvOpenRank.client.altmetrics.serverError")
            );
        } catch (ClientException $error) {
            error_log($this->describeFailure($error));
            throw new \Exception(
                __("plugins.generic.fgvOpenRank.client.altmetrics.clientError")
            );
        } catch (TransferException $error) {
            error_log($this->describeFailure($error));
            throw new \Exception(
                __("plugins.generic.fgvOpenRank.client.altmetrics.transferError")
            );
        } catch (GuzzleException $error) {
            throw new \Exception('Altmetrics Error: ' . $this->describeFailure($error));
        }
    }

    /**
     * Guzzle messages carry the request URI, whose query holds the API key,
     * so only the exception class and the HTTP status are reported.
     */
    private function describeFailure(GuzzleException $error): string
    {
        $description = 'Altmetric request failed: ' . (new \ReflectionClass($error))->getShortName();

        if ($error instanceof RequestException && $error->hasResponse()) {
            $description .= ' (HTTP ' . $error->getResponse()->getStatusCode() . ')';
        }

        return $description;
    }
}
