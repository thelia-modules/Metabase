<?php

namespace Metabase\Service;

use Metabase\Exception\MetabaseException;
use Metabase\Metabase;
use Symfony\Component\HttpClient\HttpClient;
use Thelia\Core\Translation\Translator;

class MetabaseService
{
    private $client;

    public function __construct()
    {
        $this->client = new HttpClient();
    }

    public function getDashboards(): string
    {
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'GET',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/dashboard/',
            [
                'headers' => [
                        'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();
        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->getDashboards();
        }

        return $metabaseResponse->getContent();
    }

    public function createDashboard(
        String $name,
        String $description,
        int $collectionId
    ){
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/dashboard/',
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
                'json' => [
                    "name" =>  $name,
                    "description" =>  $description,
                    "collection_id" => $collectionId
                ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();
        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->createDashboard($name, $description, $collectionId);
        }
        return $metabaseResponse->getContent();
    }

    public function createCard(
        Array $visualization_settings,
        Array $parameters,
        String $name,
        String $description,
        Array $dataset_query,
        String $display,
        int $collectionId
    ){
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/card',
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
                'json' => [
                    "visualization_settings" => $visualization_settings,
                    "parameters" => $parameters,
                    "name" =>  $name,
                    "description" =>  $description,
                    "dataset_query" =>  $dataset_query,
                    "display" =>  $display,
                    "collection_id" => $collectionId
                ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();
        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->createCard($visualization_settings, $parameters, $name, $description, $dataset_query, $display, $collectionId);
        }

        return $metabaseResponse->getContent();
    }

    public function addCardToDashboard(int $dashboardId, int $cardId){

        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/dashboard/'. $dashboardId.'/cards',
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
                'json' => [
                    "cardId" => $cardId
                ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();
        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->addCardToDashboard($dashboardId, $cardId);
        }
        return $metabaseResponse->getContent();
    }

    public function resizeCards(
        int $dashboardId,
        int $dashboardCardId,
        array $parameter_mappings,
        array $series = [],
        int $row = 0,
        int $col = 0,
        int $size_x = 18,
        int $size_y = 5
    ){

        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'PUT',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/dashboard/'. $dashboardId.'/cards',
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
                'json' => [
                    "cards" => [
                        [
                            "id" => $dashboardCardId,
                            "row" => $row,
                            "col" => $col,
                            "size_x" => $size_x,
                            "size_y" => $size_y,
                            "series" => $series,
                            "parameter_mappings" => $parameter_mappings
                        ]
                    ]
                ]
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();
        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->resizeCards(
                $dashboardId,
                $dashboardCardId,
                $parameter_mappings,
                $series,
                $row,
                $col,
                $size_x,
                $size_y);
        }
        return $metabaseResponse->getContent();
    }

    public function publishDashboard(int $dashboardId, array $embedding_params, array $parameters){

        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'PUT',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/dashboard/'. $dashboardId,
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
                'json' => [
                    "enable_embedding" => true,
                    "embedding_params" => $embedding_params,
                    "parameters" => $parameters
                ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();
        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->publishDashboard($dashboardId, $embedding_params, $parameters);
        }

        return $metabaseResponse->getContent();
    }

    // session_token expire every 14 days so we have to obtain a new one
    public function getSessionToken()
    {
        if (!Metabase::getConfigValue(Metabase::CONFIG_USERNAME) ||
            !(Metabase::getConfigValue(Metabase::CONFIG_PASS)) ||
            !(Metabase::getConfigValue(Metabase::CONFIG_KEY_URL))) {
            throw new MetabaseException((Metabase::ERROR_CONFIG_MESSAGE));
        }

        $client = HttpClient::create();
        $sessionResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/session',
            [
                'headers' => [
                    'Content-Type: application/json',
                ],
                'json' => [
                        'username' => Metabase::getConfigValue(Metabase::CONFIG_USERNAME),
                        'password' => Metabase::getConfigValue(Metabase::CONFIG_PASS),
                    ],
            ]
        );

        if ($sessionResponse->getStatusCode() === !200) {
            throw new MetabaseException(Metabase::ERROR_TOKEN_MESSAGE);
        }

        $sessionToken = json_decode($sessionResponse->getContent(), true)['id'];
        Metabase::setConfigValue(Metabase::CONFIG_SESSION_TOKEN, $sessionToken);
    }

    public function importBDD(string $metabaseName, string $dbName, string $engine, string $host, string $port, string $user, string $password)
    {
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/database',
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
                'json' => [
                    'name' => $metabaseName,
                    'engine' => $engine,
                    "details" => [
                        "host" => $host,
                        "port" => $port,
                        "dbname" => $dbName,
                        "user" => $user,
                        "password" => $password,
                        "advanced-options" => true,
                        "let-user-control-scheduling" => true,
                    ],
                    "is_full_sync" => true
                ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();
        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->importBDD($metabaseName, $dbName, $engine, $host, $port, $user, $password);
        }
        return $metabaseResponse->getContent();
    }

    public function createCollection(int $databaseId, String $name, int $parentId = null)
    {
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/collection',
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
                'json' => [
                    "name"=> $name,
                    "color"=> "#FFA500",
                    "parent_id" => $parentId
                ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();

        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->createCollection($databaseId, $name, $parentId);
        }
        return $metabaseResponse->getContent();
    }

    public function checkMetabaseState(String $databaseId)
    {
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'GET',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/database/'. $databaseId,
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();

        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->checkMetabaseState($databaseId);
        }

        return $metabaseResponse->getContent();
    }

    public function getAllField(int $databaseId)
    {
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'GET',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/database/'.$databaseId.'/fields',
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();

        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->getAllField($databaseId);
        }
        return $metabaseResponse->getContent();
    }

    public function getCard(int $cardId)
    {
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'GET',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/card/'.$cardId,
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();

        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->getCard($cardId);
        }
        return $metabaseResponse->getContent();
    }

    public function updateSyncingParameters(
        int $dashboardId,
        bool $isFullSync,
        bool $isOnDemand,
        bool $refingerprint,
        String $scheduleSyncType,
        String $scheduleScanType,
        int $syncHours = 0,
        int $syncMin = 0,
        int $scanHours = 0,
        $scanFrame = null,
        $scanDay = null
    ){
        if (!Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN)) {
            $this->getSessionToken();
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'PUT',
            Metabase::getConfigValue(Metabase::CONFIG_KEY_URL).'/'.'api/database/'. $dashboardId,
            [
                'headers' => [
                    'X-Metabase-Session' => Metabase::getConfigValue(Metabase::CONFIG_SESSION_TOKEN),
                    'Content-Type: application/json',
                ],
                'json' => [
                    "engine" => Metabase::getConfigValue(Metabase::CONFIG_METABASE_ENGINE),
                    "name" => Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_NAME),
                    "details" => ["let-user-control-scheduling"=> true],
                    "can_manage" => true,
                    "is_full_sync" => $isFullSync,
                    "is_on_demand" => $isOnDemand,
                    "schedules" => [
                        "metadata_sync" => [
                            "schedule_minute" => $syncMin,
                            "schedule_day"=> null,
                            "schedule_frame"=> null,
                            "schedule_hour" => $syncHours,
                            "schedule_type" => $scheduleSyncType
                        ],
                        "cache_field_values" => [
                            "schedule_minute" => 0,
                            "schedule_day"=> $scanDay,
                            "schedule_frame"=> $scanFrame,
                            "schedule_hour" => $scanHours,
                            "schedule_type" => $scheduleScanType
                        ]
                    ],
                    "refingerprint" => $refingerprint
                ]
            ]
        );

        $statusCode = $metabaseResponse->getStatusCode();
        if (401 == $statusCode) {
            $this->getSessionToken();

            return $this->updateSyncingParameters(
                $dashboardId,
                $isFullSync,
                $isOnDemand,
                $refingerprint,
                $scheduleSyncType,
                $scheduleScanType,
                $syncHours,
                $syncMin,
                $scanHours,
                $scanFrame,
                $scanDay);
        }

        return $metabaseResponse->getContent();
    }

    public function searchField(Array $fields, String $fieldName, String $tableName){

        foreach ($fields as $field){
            if ($field->name === $fieldName && $field->table_name === $tableName){
                return $field->id;
            }
        }
        return null;
    }

    public function getDefaultOrderType()
    {
        $defaultValues = [];
        $default = str_split(Metabase::getConfigValue(Metabase::CONFIG_METABASE_ORDER_TYPE));

        $i = 0;
        foreach ($default as $s){
            if ($i%2 <> 1){
                $defaultValues[] = intval($s);
            }
            $i=$i+1;
        }

        return $defaultValues;
    }

    public function verifyFormSyncing(array $data): array
    {
        $verifiedData = [];

        if ($data["syncingOption"]=== null){
            throw new \Exception(Translator::getInstance()->trans("Choose a Syncing Option"));
        }

        if ($data["syncingSchedule"]=== null){
            throw new \Exception(Translator::getInstance()->trans("Choose a Syncing Schedule"));
        }

        if ($data["syncingOption"]!== "sync_only" && $data["scanningSchedule"]=== null){
            throw new \Exception(Translator::getInstance()->trans("Choose a Scanning Schedule"));
        }

        $verifiedData["is_full_sync"] = false;
        $verifiedData["is_on_demand"] = false;
        $verifiedData["refingerprint"] = $data["refingerprint"];
        $verifiedData["syncing_schedule"] = $data["syncingSchedule"];
        $verifiedData["scanning_schedule"]= $data["scanningSchedule"];

        if ($data["syncingOption"]=== "is_full_sync"){
            $verifiedData["is_full_sync"] = true;
        }

        if ($data["syncingOption"]=== "is_on_demand"){
            $verifiedData["is_on_demand"] = true;
        }

        $verifiedData["sync_hours"] = 0;
        $verifiedData["sync_minutes"] = 0;

        if ($data["syncingSchedule"] == "hourly"){
            if ($data["syncingTime"] < 0 || $data["syncingTime"] > 60){
                throw new \Exception(Translator::getInstance()->trans("Syncing time must be in range [0-60]"));
            }
            $verifiedData["sync_minutes"] = $data["syncingTime"];
        }

        if ($data["syncingSchedule"] == "daily"){
            if ($data["syncingTime"] < 0 || $data["syncingTime"] > 24){
                throw new \Exception(Translator::getInstance()->trans("Syncing time must be in range [0-24]"));
            }
            $verifiedData["sync_hours"] = $data["syncingTime"];
        }

        if ($data["scanningTime"] < 0 || $data["scanningTime"] > 24){
            throw new \Exception(Translator::getInstance()->trans("Scanning time must be in range [0-24]"));
        }
        $verifiedData["scan_hours"] = $data["scanningTime"];
        $verifiedData["scan_frame"] = null;
        $verifiedData["scan_day"] = null;

        if ($data["scanningSchedule"] === "monthly")
        {
            if ($data["scanningFrame"] === null)
            {
                $verifiedData["scanning_schedule"] = "weekly";
            }
            $verifiedData["scan_frame"] = $data["scanningFrame"];

            if ($data["scanningFrame"] === null && $data["scanningDay"] === null)
            {
                throw new \Exception(Translator::getInstance()->trans("You can't select Calendar day for Weekly scan"));
            }
            if ($data["scanningFrame"] !== "mid")
            {
                $verifiedData["scan_day"] = $data["scanningDay"];
            }
        }

        return $verifiedData;
    }
}
