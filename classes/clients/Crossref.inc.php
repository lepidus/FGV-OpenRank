<?php

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;

class Crossref
{
    private const BASE_URL = 'https://api.crossref.org/';
    private const WORKS_ENDPOINT = 'works';
    private $httpClient;

    public function __construct($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function fetchMostCitedSubmissions(string $issn, int $limit): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                self::BASE_URL . self::WORKS_ENDPOINT,
                [
                    'query' => [
                        'filter' => 'issn:' . $issn,
                        'sort'   => 'is-referenced-by-count',
                        'order'  => 'desc',
                        'rows'   => $limit,
                    ],
                ]
            );
            return json_decode($response->getBody()->getContents(), true);
        } catch (ServerException $error) {
            error_log($error->getMessage());
            throw new \Exception(__("plugins.generic.rankingPlugin.client.serverError"));
        } catch (ClientException $error) {
            error_log($error->getMessage());
            throw new \Exception(__("plugins.generic.rankingPlugin.client.clientError"));
        } catch (TransferException $error) {
            error_log($error->getMessage());
            throw new \Exception(__("plugins.generic.rankingPlugin.client.transferError"));
        } catch (GuzzleException $error) {
            throw new \Exception("Crossref Client Error" . $error->getMessage(), 0, $error);
        }
    }
}
