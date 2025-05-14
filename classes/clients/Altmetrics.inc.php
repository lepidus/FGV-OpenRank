<?php

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;

class Altmetrics
{
    private const BASE_URL = ' https://api.altmetric.com/v1/';
    private const ALTMETRICS_ENDPOINT = 'doi';
    private $httpClient;

    public function __construct($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function fetchAltmetrics(string $doi): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                self::BASE_URL . self::ALTMETRICS_ENDPOINT . '/' . $doi
            );
            return json_decode($response->getBody()->getContents(), true);
        } catch (ServerException $error) {
            error_log($error->getMessage());
            throw new \Exception(__("plugins.generic.rankingPlugin.client.altmetrics.serverError"));
        } catch (ClientException $error) {
            error_log($error->getMessage());
            throw new \Exception(__("plugins.generic.rankingPlugin.client.altmetrics.clientError"));
        } catch (GuzzleException $error) {
            throw new \Exception("Altmetrics Error: " . $error->getMessage(), 0, $error);
        }
    }
}
