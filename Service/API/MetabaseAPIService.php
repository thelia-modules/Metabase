<?php

namespace Metabase\Service\API;

use Metabase\Exception\MetabaseException;
use Metabase\Metabase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Core\Translation\Translator;

class MetabaseAPIService
{
    /**
     * session_token expire every 14 days, so we have to get a new one.
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws MetabaseException
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function getSessionToken(): string
    {
        if (!$this->isTokenExpired()) {
            return Metabase::getConfigValue(Metabase::METABASE_TOKEN_SESSION_CONFIG_KEY);
        }

        if (!Metabase::getConfigValue(Metabase::METABASE_USERNAME_CONFIG_KEY)
            || !Metabase::getConfigValue(Metabase::METABASE_PASSWORD_CONFIG_KEY)
            || !Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY)) {
            throw new MetabaseException(Metabase::ERROR_CONFIG_MESSAGE);
        }

        $client = HttpClient::create();
        $sessionResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/session',
            [
                'headers' => [
                    'Content-Type: application/json',
                ],
                'json' => [
                    'username' => Metabase::getConfigValue(Metabase::METABASE_USERNAME_CONFIG_KEY),
                    'password' => Metabase::getConfigValue(Metabase::METABASE_PASSWORD_CONFIG_KEY),
                ],
            ]
        );

        if (200 !== $sessionResponse->getStatusCode()) {
            throw new MetabaseException(Metabase::ERROR_TOKEN_MESSAGE);
        }

        $sessionToken = json_decode($sessionResponse->getContent(), true, 512, JSON_THROW_ON_ERROR)['id'];
        Metabase::setConfigValue(Metabase::METABASE_TOKEN_SESSION_CONFIG_KEY, $sessionToken);
        Metabase::setConfigValue(
            Metabase::METABASE_TOKEN_EXPIRATION_DATE_CONFIG_KEY,
            (new \DateTime())->modify('+1 day')->format('Y-m-d')
        );

        return $sessionToken;
    }

    public function isTokenExpired(): bool
    {
        if (null !== $tokenDate = Metabase::getConfigValue(Metabase::METABASE_TOKEN_EXPIRATION_DATE_CONFIG_KEY)) {
            $tokenDateFormat = \DateTime::createFromFormat('Y-m-d', $tokenDate);
            $now = new \DateTime();

            if ($now < $tokenDateFormat) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function getPublicDashboards()
    {
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'GET',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/dashboard/public',
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function createDashboard(string $name, string $description, int $collectionId)
    {
        $client = HttpClient::create();

        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/dashboard/',
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
                'json' => [
                    'name' => $name,
                    'description' => $description,
                    'collection_id' => $collectionId,
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     * @throws \JsonException
     */
    public function createCard(
        array $visualizationSettings,
        array $parameters,
        string $name,
        string $description,
        array $datasetQuery,
        string $display,
        int $collectionId
    ) {
        $client = HttpClient::create();

        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/card',
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
                'json' => [
                    'visualization_settings' => $visualizationSettings,
                    'parameters' => $parameters,
                    'name' => $name,
                    'description' => $description,
                    'dataset_query' => $datasetQuery,
                    'display' => $display,
                    'collection_id' => $collectionId,
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     * @throws \JsonException
     */
    public function embedDashboard(int $dashboardId, array $embeddingParams, array $dashcards, array $parameters)
    {
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'PUT',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/dashboard/'.$dashboardId,
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
                'json' => [
                    'enable_embedding' => true,
                    'embedding_params' => $embeddingParams,
                    'parameters' => $parameters,
                    'width' => 'full',
                    'dashcards' => $dashcards,
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     */
    public function publishDashboard(int $dashboardId)
    {
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/dashboard/'.$dashboardId.'/public_link',
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
                'json' => [],
            ]
        );

        return json_decode($metabaseResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws MetabaseException
     */
    public function importDatabase(string $metabaseName, string $dbName, string $engine, string $host, string $port, string $user, string $password)
    {
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/database',
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
                'json' => [
                    'name' => $metabaseName,
                    'engine' => $engine,
                    'details' => [
                        'host' => $host,
                        'port' => $port,
                        'dbname' => $dbName,
                        'user' => $user,
                        'password' => $password,
                        'advanced-options' => true,
                    ],
                    'is_full_sync' => true,
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws MetabaseException
     */
    public function createCollection(string $name, ?int $parentId = null)
    {
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'POST',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/collection',
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
                'json' => [
                    'name' => $name,
                    'color' => '#FFA500',
                    'parent_id' => $parentId,
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws MetabaseException
     */
    public function checkMetabaseState()
    {
        if (null === $databaseId = Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY)) {
            throw new \RuntimeException('database id is null');
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'GET',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/database/'.$databaseId,
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws MetabaseException
     */
    public function getAllField()
    {
        if (null === $databaseId = Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY)) {
            throw new \RuntimeException('database id is null');
        }

        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'GET',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/database/'.$databaseId.'/fields',
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws MetabaseException
     */
    public function getCard(int $cardId)
    {
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'GET',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/card/'.$cardId,
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws MetabaseException
     */
    public function updateSyncingParameters(
        int $dashboardId,
        bool $isFullSync,
        bool $isOnDemand,
        bool $refingerprint,
        string $scheduleSyncType,
        string $scheduleScanType,
        int $syncHours = 0,
        int $syncMin = 0,
        int $scanHours = 0,
        $scanFrame = null,
        $scanDay = null
    ) {
        $client = HttpClient::create();
        $metabaseResponse = $client->request(
            'PUT',
            Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY).'/api/database/'.$dashboardId,
            [
                'headers' => [
                    'X-Metabase-Session' => $this->getSessionToken(),
                    'Content-Type: application/json',
                ],
                'json' => [
                    'engine' => Metabase::getConfigValue(Metabase::METABASE_ENGINE_CONFIG_KEY),
                    'name' => Metabase::getConfigValue(Metabase::METABASE_DB_NAME_CONFIG_KEY),
                    'details' => ['let-user-control-scheduling' => true],
                    'can_manage' => true,
                    'is_full_sync' => $isFullSync,
                    'is_on_demand' => $isOnDemand,
                    'schedules' => [
                        'metadata_sync' => [
                            'schedule_minute' => $syncMin,
                            'schedule_day' => null,
                            'schedule_frame' => null,
                            'schedule_hour' => $syncHours,
                            'schedule_type' => $scheduleSyncType,
                        ],
                        'cache_field_values' => [
                            'schedule_minute' => 0,
                            'schedule_day' => $scanDay,
                            'schedule_frame' => $scanFrame,
                            'schedule_hour' => $scanHours,
                            'schedule_type' => $scheduleScanType,
                        ],
                    ],
                    'refingerprint' => $refingerprint,
                ],
            ]
        );

        return json_decode($metabaseResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws MetabaseException
     */
    public function searchField(array $fields, string $fieldName, string $tableName): int
    {
        foreach ($fields as $field) {
            if ($field->name === $fieldName && $field->table_name === $tableName) {
                return $field->id;
            }
        }

        throw new MetabaseException('field '.$fieldName.' not found in table '.$tableName);
    }

    public function getDefaultOrderType(): array
    {
        $defaultValues = [];
        $default = str_split(Metabase::getConfigValue(Metabase::METABASE_ORDER_TYPE_CONFIG_KEY));

        $i = 0;
        foreach ($default as $s) {
            if (1 !== $i % 2) {
                $defaultValues[] = (int) $s;
            }
            ++$i;
        }

        return $defaultValues;
    }

    public function verifyFormSyncing(array $data): array
    {
        $verifiedData = [];

        if (null === $data['syncingOption']) {
            throw new \RuntimeException(Translator::getInstance()->trans('Choose a Syncing Option'));
        }

        if (null === $data['syncingSchedule']) {
            throw new \RuntimeException(Translator::getInstance()->trans('Choose a Syncing Schedule'));
        }

        if ('sync_only' !== $data['syncingOption'] && null === $data['scanningSchedule']) {
            throw new \RuntimeException(Translator::getInstance()->trans('Choose a Scanning Schedule'));
        }

        $verifiedData['is_full_sync'] = false;
        $verifiedData['is_on_demand'] = false;
        $verifiedData['refingerprint'] = $data['refingerprint'];
        $verifiedData['syncing_schedule'] = $data['syncingSchedule'];
        $verifiedData['scanning_schedule'] = $data['scanningSchedule'];

        if ('is_full_sync' === $data['syncingOption']) {
            $verifiedData['is_full_sync'] = true;
        }

        if ('is_on_demand' === $data['syncingOption']) {
            $verifiedData['is_on_demand'] = true;
        }

        $verifiedData['sync_hours'] = 0;
        $verifiedData['sync_minutes'] = 0;

        if ('hourly' === $data['syncingSchedule']) {
            if ($data['syncingTime'] < 0 || $data['syncingTime'] > 60) {
                throw new \RuntimeException(Translator::getInstance()->trans('Syncing time must be in range [0-60]'));
            }
            $verifiedData['sync_minutes'] = $data['syncingTime'];
        }

        if ('daily' === $data['syncingSchedule']) {
            if ($data['syncingTime'] < 0 || $data['syncingTime'] > 24) {
                throw new \RuntimeException(Translator::getInstance()->trans('Syncing time must be in range [0-24]'));
            }
            $verifiedData['sync_hours'] = $data['syncingTime'];
        }

        if ($data['scanningTime'] < 0 || $data['scanningTime'] > 24) {
            throw new \RuntimeException(Translator::getInstance()->trans('Scanning time must be in range [0-24]'));
        }
        $verifiedData['scan_hours'] = $data['scanningTime'];
        $verifiedData['scan_frame'] = null;
        $verifiedData['scan_day'] = null;

        if ('monthly' === $data['scanningSchedule']) {
            if (null === $data['scanningFrame']) {
                $verifiedData['scanning_schedule'] = 'weekly';
            }
            $verifiedData['scan_frame'] = $data['scanningFrame'];

            if (null === $data['scanningFrame'] && null === $data['scanningDay']) {
                throw new \RuntimeException(Translator::getInstance()->trans("You can't select Calendar day for Weekly scan"));
            }
            if ('mid' !== $data['scanningFrame']) {
                $verifiedData['scan_day'] = $data['scanningDay'];
            }
        }

        return $verifiedData;
    }
}
