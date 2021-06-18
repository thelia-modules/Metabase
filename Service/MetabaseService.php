<?php

namespace Metabase\Service;

use Metabase\Metabase;
use Symfony\Component\Config\Definition\Exception\Exception;
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
        }

        try {
            $dashboards = $metabaseResponse->getContent();
            return $dashboards;

        }catch(\Exception $e){
            $metabaseResponse->getContent(true);
        }
    }

    // session_token expire every 14 days so we have to obtain a new one

    public function getSessionToken() {

        if (!Metabase::getConfigValue(Metabase::CONFIG_USERNAME)||
            !(Metabase::getConfigValue(Metabase::CONFIG_PASS))||
            !(Metabase::getConfigValue(Metabase::CONFIG_KEY_URL)))
            {
                throw new Exception(Metabase::ERROR_CONFIG_MESSAGE);
            }

        $client = HttpClient::create();
        $sessionResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL) . '/' . 'api/session',
            [
                'headers' => [
                    'Content-Type: application/json'
                ],
                'json' =>
                    [
                        'username' => Metabase::getConfigValue(Metabase::CONFIG_USERNAME),
                        'password' => Metabase::getConfigValue(Metabase::CONFIG_PASS)
                    ]
            ]
        );

       try {
            $sessionToken = json_decode($sessionResponse->getContent(), true)['id'];
            Metabase::setConfigValue(Metabase::CONFIG_SESSION_TOKEN, $sessionToken);
            $this->getDashboards();

        }catch(\Exception $e){
            return $sessionResponse->getContent(true);
        }
    }
}

// http://10.63.1.96:3000
// 88133734cdb97ce62ecf513e36011d56cfc97f6d2c170e0e0125ab52511b1327
// 2547e1b9-c3b3-4200-9006-b7fbf77a94a4
// lcrenais@openstudio.fr
// sT7NdVLa7g4RWZ
