<?php

namespace Metabase\Service\Base;

use Metabase\Exception\MetabaseException;
use Metabase\Metabase;
use Metabase\Service\API\MetabaseAPIService;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Model\LangQuery;
use Thelia\Model\OrderStatusQuery;

abstract class AbstractMetabaseService implements MetabaseInterface
{
    private string $uuidDate1;
    private string $uuidDate2;
    private string $uuidOrderStatus;
    private string $uuidParamDate1;
    private string $uuidParamDate2;
    private string $uuidParamOrderStatus;

    public function __construct(protected MetabaseAPIService $metabaseAPIService)
    {
        $this->uuidDate1 = uniqid('', true);
        $this->uuidDate2 = uniqid('', true);
        $this->uuidOrderStatus = uniqid('', true);
        $this->uuidParamDate1 = uniqid('', true);
        $this->uuidParamDate2 = uniqid('', true);
        $this->uuidParamOrderStatus = uniqid('', true);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function generateDashboardMetabase(
        string $dashboardName,
        string $descriptionDashboard,
        ?int $collectionId = null
    ) {
        return $this->metabaseAPIService->createDashboard($dashboardName, $descriptionDashboard, $collectionId);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function generateCardMetabase(
        string $name,
        string $description,
        string $display,
        int $collectionId,
        string $query,
        array $fields,
        array $defaultFields = []
    ) {
        $defaultOrderStatus = $this->getDefaultOrderStatus();

        return $this->metabaseAPIService->createCard(
            $this->buildVisualizationSettings(),
            $this->buildParameters($defaultOrderStatus, $defaultFields),
            $name,
            $description,
            $this->buildDatasetQuery($query, $defaultOrderStatus, $fields, $defaultFields),
            $display,
            $collectionId
        );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws MetabaseException
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function generateCustomCardMetabase(
        array $visualizationSettings,
        array $parameters,
        string $name,
        string $description,
        array $datasetQuery,
        string $display,
        int $collectionId
    ) {
        return $this->metabaseAPIService->createCard(
            $visualizationSettings,
            $parameters,
            $name,
            $description,
            $datasetQuery,
            $display,
            $collectionId
        );
    }

    public function formatDashboardCard(
        int $cardId,
        array $series = [],
        int $row = 0,
        int $col = 0,
        int $sizeX = 24,
        int $sizeY = 8,
        int ...$cardsId,
    ): array {
        return [
            'id' => $cardId,
            'card_id' => $cardId,
            'row' => $row,
            'col' => $col,
            'size_x' => $sizeX,
            'size_y' => $sizeY,
            'series' => $series,
            'parameter_mappings' => $this->getCardParameterMapping(...$cardsId),
        ];
    }

    public function formatSeries(int ...$cardsId): array
    {
        $cards = [];

        foreach ($cardsId as $cardId) {
            $cards[] = ['id' => $cardId];
        }

        return $cards;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws MetabaseException
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function embedDashboard(int $dashboardId, array $parameters, array $dashcards, array $defaultFields = [])
    {
        return $this->metabaseAPIService->embedDashboard(
            $dashboardId,
            $parameters,
            $dashcards,
            $this->getDashboardParameters($defaultFields)
        );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function publishDashboard(int $dashboardId)
    {
        return $this->metabaseAPIService->publishDashboard($dashboardId);
    }

    public function getDefaultOrderStatus(): array
    {
        $defaultValues = [];
        $default = explode(',', Metabase::getConfigValue(Metabase::METABASE_ORDER_TYPE_CONFIG_KEY));

        $locale = LangQuery::create()->findOneByByDefault(1)?->getLocale();

        foreach ($default as $s) {
            $orderStatus = OrderStatusQuery::create()
                ->useOrderStatusI18nQuery()
                ->filterByLocale($locale)
                ->endUse()
                ->findOneById($s);

            $defaultValues[] = $orderStatus?->setLocale($locale)->getTitle();
        }

        return $defaultValues;
    }

    public function getValuesSourceConfigValuesOrderStatus(): array
    {
        $statuses = [];

        $locale = LangQuery::create()->findOneByByDefault(1)?->getLocale();

        $orderStatus = OrderStatusQuery::create()
            ->useOrderStatusI18nQuery()
            ->filterByLocale($locale)
            ->endUse()
            ->find();

        foreach ($orderStatus as $status) {
            $statuses[] = $status->setLocale($locale)->getTitle();
        }

        return $statuses;
    }

    public function getUuidDate1(): string
    {
        return $this->uuidDate1;
    }

    public function getUuidDate2(): string
    {
        return $this->uuidDate2;
    }

    public function getUuidOrderStatus(): string
    {
        return $this->uuidOrderStatus;
    }

    public function getUuidParamDate1(): string
    {
        return $this->uuidParamDate1;
    }

    public function getUuidParamDate2(): string
    {
        return $this->uuidParamDate2;
    }

    public function getUuidParamOrderStatus(): string
    {
        return $this->uuidParamOrderStatus;
    }
}
