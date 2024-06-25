<?php

namespace Metabase\Service;

use Metabase\Exception\MetabaseException;
use Metabase\Metabase;
use Metabase\Service\API\MetabaseAPIService;
use Metabase\Service\Base\AbstractMetabaseService;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Core\Translation\Translator;

class StatisticBrandService extends AbstractMetabaseService
{
    private string $uuidBrand;
    private string $uuidParamBrand;

    public function __construct(protected MetabaseAPIService $metabaseAPIService)
    {
        parent::__construct($metabaseAPIService);

        $this->uuidBrand = uniqid('', true);
        $this->uuidParamBrand = uniqid('', true);
    }

    /**
     * @throws MetabaseException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws \JsonException
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function generateStatisticMetabase(int $collectionId, array $fields): void
    {
        $translator = Translator::getInstance();

        $defaultFields = ['date' => 'past1years'];
        $defaultFields2 = ['date' => 'thisyear'];

        $dashboard = $this->generateDashboardMetabase(
            $translator->trans('Dashboard Sales Brand', [], Metabase::DOMAIN_NAME),
            $translator->trans('Sales Statistic by Brand dashboard', [], Metabase::DOMAIN_NAME),
            $collectionId
        );

        $dashboard2 = $this->generateDashboardMetabase(
            $translator->trans('Dashboard Count Brand', [], Metabase::DOMAIN_NAME),
            $translator->trans('Count Statistic by Brand dashboard', [], Metabase::DOMAIN_NAME),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator->trans('BrandsSalesCard_', [], Metabase::DOMAIN_NAME).'1',
            $translator->trans('Card of Sales Statistic by Brand period', [], Metabase::DOMAIN_NAME).' 1',
            'line',
            $collectionId,
            $this->getSqlQueryMain(),
            $fields,
            $defaultFields
        );

        $card2 = $this->generateCardMetabase(
            $translator->trans('BrandsSalesCard_', [], Metabase::DOMAIN_NAME).'2',
            $translator->trans('Card of Sales Statistic by Brand period', [], Metabase::DOMAIN_NAME).' 2',
            'line',
            $collectionId,
            $this->getSqlQueryMain(),
            $fields,
            $defaultFields2
        );

        $defaultOrderType = $this->metabaseAPIService->getDefaultOrderType();

        $card3 = $this->generateCustomCardMetabase(
            $this->buildCustomVisualizationSettings(),
            $this->buildParameters($defaultOrderType, $defaultFields),
            $translator->trans('BrandsCard_', [], Metabase::DOMAIN_NAME).'1',
            $translator->trans('Card of Count Statistic by Brand period', [], Metabase::DOMAIN_NAME).' 1',
            $this->buildDatasetQuery($this->getSqlQuerySecondary(), $defaultOrderType, $fields, $defaultFields),
            'line',
            $collectionId,
        );

        $card4 = $this->generateCustomCardMetabase(
            $this->buildCustomVisualizationSettings(),
            $this->buildParameters($defaultOrderType, $defaultFields2),
            $translator->trans('BrandsCard_', [], Metabase::DOMAIN_NAME).'2',
            $translator->trans('Card of Count Statistic by Brand period', [], Metabase::DOMAIN_NAME).' 2',
            $this->buildDatasetQuery($this->getSqlQuerySecondary(), $defaultOrderType, $fields, $defaultFields2),
            'line',
            $collectionId,
        );

        $series = $this->formatSeries($card2->id);
        $series2 = $this->formatSeries($card2->id);

        $dashboardCard = $this->formatDashboardCard($card->id, $series, 0, 0, 24, 8, $card->id, $card2->id);
        $dashboardCard2 = $this->formatDashboardCard($card->id, $series2, 0, 0, 24, 8, $card3->id, $card4->id);

        $this->embedDashboard($dashboard->id, ['date_1' => 'enabled', 'date_2' => 'enabled', 'brand_reference' => 'enabled', 'orderType' => 'enabled'], [$dashboardCard]);
        $this->embedDashboard($dashboard2->id, ['date_1' => 'enabled', 'date_2' => 'enabled', 'brand_reference' => 'enabled', 'orderType' => 'enabled'], [$dashboardCard2]);

        $this->publishDashboard($dashboard->id);
        $this->publishDashboard($dashboard2->id);
    }

    private function getSqlQueryMain(): string
    {
        return 'SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS TOTAL, 
            DATE_FORMAT(`order`.`invoice_date`,"%b") as DATE
            FROM `order` 
            INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
            INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
            INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
            WHERE 1=0 [[or {{brand}}]] and {{date}} [[and {{orderType}}]]
            group by DATE_FORMAT(`order`.`invoice_date`, "%m"), DATE
            order by DATE_FORMAT(`order`.`invoice_date`, "%m")'
        ;
    }

    private function getSqlQuerySecondary(): string
    {
        return 'SELECT SUM(order_product.quantity) AS TOTAL, 
                DATE_FORMAT(`order`.`invoice_date`,"%b") as DATE
                FROM `order` 
                INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
                INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
                WHERE 1=0 [[or {{brand}}]] and {{date}} [[and {{orderType}}]]
                group by DATE_FORMAT(`order`.`invoice_date`, "%m"), DATE
                order by DATE_FORMAT(`order`.`invoice_date`, "%m")'
        ;
    }

    public function buildVisualizationSettings(): array
    {
        return [
            'graph.dimensions' => ['DATE'],
            'graph.series_order_dimension' => null,
            'graph.series_order' => null,
            'graph.metrics' => ['TOTAL'],
            'column_settings' => [
                '["name","TOTAL"]' => [
                    'suffix' => '€',
                    'number_separators' => ', ',
                ],
            ],
        ];
    }

    public function buildCustomVisualizationSettings(): array
    {
        return [
            'graph.dimensions' => ['DATE'],
            'graph.series_order_dimension' => null,
            'graph.series_order' => null,
            'graph.metrics' => ['TOTAL'],
            'column_settings' => [],
        ];
    }

    public function buildParameters(array $defaultOrderType, array $defaultFields = []): array
    {
        return [
            [
                'id' => $this->uuidBrand,
                'type' => 'string/=',
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'brand',
                    ],
                ],
                'name' => 'Brand',
                'slug' => 'brand',
                'default' => null,
            ],
            [
                'id' => $this->getUuidDate1(),
                'type' => 'date/all-options',
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'date',
                    ],
                ],
                'name' => 'DATE',
                'slug' => 'date',
                'default' => $defaultFields['date'],
            ],
            [
                'id' => $this->getUuidOrderType(),
                'type' => 'string/=',
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'orderType',
                    ],
                ],
                'name' => 'Ordertype',
                'slug' => 'orderType',
                'default' => $defaultOrderType,
            ],
        ];
    }

    /**
     * @throws MetabaseException
     */
    public function buildDatasetQuery(string $query, array $defaultOrderType, array $fields, array $defaultFields = []): array
    {
        $fieldBrandTitle = $this->metabaseAPIService->searchField($fields, 'title', 'brand_i18n');
        $fieldDate = $this->metabaseAPIService->searchField($fields, 'invoice_date', 'order');
        $fieldOrderType = $this->metabaseAPIService->searchField($fields, 'status_id', 'order');

        return [
            'database' => (int) Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY),
            'native' => [
                'template-tags' => [
                    'brand' => [
                        'id' => $this->uuidBrand,
                        'name' => 'brand',
                        'display-name' => 'Brand',
                        'type' => 'dimension',
                        'dimension' => [
                            'field',
                            $fieldBrandTitle,
                            null,
                        ],
                        'widget-type' => 'string/=',
                        'default' => null,
                    ],
                    'date' => [
                        'id' => $this->getUuidDate1(),
                        'name' => 'date',
                        'display-name' => 'DATE',
                        'type' => 'dimension',
                        'dimension' => [
                            'field',
                            $fieldDate,
                            null,
                        ],
                        'widget-type' => 'date/all-options',
                        'required' => true,
                        'default' => $defaultFields['date'],
                    ],
                    'orderType' => [
                        'id' => $this->getUuidOrderType(),
                        'name' => 'orderType',
                        'display-name' => 'Ordertype',
                        'type' => 'dimension',
                        'dimension' => [
                            'field',
                            $fieldOrderType,
                            null,
                        ],
                        'widget-type' => 'string/=',
                        'default' => $defaultOrderType,
                    ],
                ],
                'query' => $query,
            ],
            'type' => 'native',
        ];
    }

    public function getCardParameterMapping(int ...$cardsId): array
    {
        return [
            [
                'parameter_id' => $this->getUuidParamDate1(),
                'card_id' => $cardsId[0],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'date',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->getUuidParamDate2(),
                'card_id' => $cardsId[1],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'date',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->uuidParamBrand,
                'card_id' => $cardsId[0],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'brand',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->uuidParamBrand,
                'card_id' => $cardsId[1],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'brand',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->getUuidParamOrderType(),
                'card_id' => $cardsId[0],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'orderType',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->getUuidParamOrderType(),
                'card_id' => $cardsId[1],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'orderType',
                    ],
                ],
            ],
        ];
    }

    public function getDashboardParameters(array $defaultFields): array
    {
        return [
            [
                'name' => 'Brand Title',
                'slug' => 'brand_title',
                'id' => $this->uuidParamBrand,
                'type' => 'string/=',
                'sectionId' => 'string',
            ],
            [
                'name' => 'Date 1',
                'slug' => 'date_1',
                'id' => $this->getUuidParamDate1(),
                'type' => 'date/all-options',
                'sectionId' => 'date',
                'default' => 'past1years',
            ],
            [
                'name' => 'Date 2',
                'slug' => 'date_2',
                'id' => $this->getUuidParamDate2(),
                'type' => 'date/all-options',
                'sectionId' => 'date',
                'default' => 'thisyear',
            ],
            [
                'name' => 'orderType',
                'slug' => 'orderType',
                'id' => $this->getUuidParamOrderType(),
                'type' => 'string/=',
                'sectionId' => 'string',
                'default' => $this->metabaseAPIService->getDefaultOrderType(),
            ],
        ];
    }
}
