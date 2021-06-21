<?php

namespace Metabase\Service;

use Metabase\Exception\MetabaseException;
use Metabase\Metabase;
use Symfony\Component\HttpClient\HttpClient;

class MetabaseService
{
    private $client;

    public function __construct()
    {
        $this->client = new HttpClient();
    }

    public function getDashboards(): string
    {
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)){
            $this->getSessionToken();
        }
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'GET',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL) . '/' . 'api/dashboard/',
            [
                'headers' =>
                    [
                        'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    ]
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();
        if ($statusCode == 401)
        {
           $this->getSessionToken();
           return $this->getDashboards();
        }
        $dashboards = $metabaseResponse->getContent();
        return $dashboards;
    }

    // session_token expire every 14 days so we have to obtain a new one

    public function getSessionToken() {

        if (!Metabase::getConfigValue(Metabase::CONFIG_USERNAME)||
            !(Metabase::getConfigValue(Metabase::CONFIG_PASS))||
            !(Metabase::getConfigValue(Metabase::CONFIG_KEY_URL)))
        {
            throw new MetabaseException((Metabase::ERROR_CONFIG_MESSAGE));
        }

        $client = HttpClient::create();
        $sessionResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL) . '/' . 'api/session',
            [
                'headers' => [
                    'Content-Type: application/json'
                ],
                'json' => [
                        'username' => Metabase::getConfigValue(Metabase::CONFIG_USERNAME),
                        'password' => Metabase::getConfigValue(Metabase::CONFIG_PASS)
                    ]
            ]
        );

        if ($sessionResponse->getStatusCode() === !200)
        {
            throw new MetabaseException(Metabase::ERROR_TOKEN_MESSAGE);
        }

        $sessionToken = json_decode($sessionResponse->getContent(), true)['id'];
        Metabase::setConfigValue(Metabase::CONFIG_SESSION_TOKEN, $sessionToken);
    }
}
