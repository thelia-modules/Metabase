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
use Thelia\Model\BrandQuery;

class BrandStatisticMetabaseService extends AbstractMetabaseService
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
    public function generateStatisticMetabase(int $collectionId, array $fields, string $locale): void
    {
        $translator = Translator::getInstance();

        $defaultFields1 =
            [
                'id' => $this->getUuidDate1(),
                'tag' => 'invoiceDate1',
                'date' => 'past1years',
            ]
        ;

        $defaultFields2 =
            [
                'id' => $this->getUuidDate2(),
                'tag' => 'invoiceDate2',
                'date' => 'thisyear',
            ]
        ;

        $dashboard = $this->generateDashboardMetabase(
            $translator->trans('Dashboard Sales Brand', [], Metabase::DOMAIN_NAME, $locale),
            $translator->trans('Sales Statistic by Brand dashboard', [], Metabase::DOMAIN_NAME, $locale),
            $collectionId
        );

        $dashboard2 = $this->generateDashboardMetabase(
            $translator->trans('Dashboard Count Brand', [], Metabase::DOMAIN_NAME, $locale),
            $translator->trans('Count Statistic by Brand dashboard', [], Metabase::DOMAIN_NAME, $locale),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator->trans('BrandsSalesCard_', [], Metabase::DOMAIN_NAME, $locale).'1',
            $translator->trans('Card of Sales Statistic by Brand period', [], Metabase::DOMAIN_NAME, $locale).' 1',
            'line',
            $collectionId,
            $this->getSqlQueryMain($defaultFields1['tag']),
            $fields,
            $locale,
            $defaultFields1
        );

        $card2 = $this->generateCardMetabase(
            $translator->trans('BrandsSalesCard_', [], Metabase::DOMAIN_NAME, $locale).'2',
            $translator->trans('Card of Sales Statistic by Brand period', [], Metabase::DOMAIN_NAME, $locale).' 2',
            'line',
            $collectionId,
            $this->getSqlQueryMain($defaultFields2['tag']),
            $fields,
            $locale,
            $defaultFields2
        );

        $defaultOrderStatus = $this->getDefaultOrderStatus($locale);

        $card3 = $this->generateCustomCardMetabase(
            $this->buildCustomVisualizationSettings(),
            $this->buildParameters($defaultOrderStatus, $defaultFields1),
            $translator->trans('BrandsCard_', [], Metabase::DOMAIN_NAME, $locale).'1',
            $translator->trans('Card of Count Statistic by Brand period', [], Metabase::DOMAIN_NAME, $locale).' 1',
            $this->buildDatasetQuery($this->getSqlQuerySecondary($defaultFields1['tag']), $defaultOrderStatus, $fields, $defaultFields1),
            'line',
            $collectionId,
        );

        $card4 = $this->generateCustomCardMetabase(
            $this->buildCustomVisualizationSettings(),
            $this->buildParameters($defaultOrderStatus, $defaultFields2),
            $translator->trans('BrandsCard_', [], Metabase::DOMAIN_NAME, $locale).'2',
            $translator->trans('Card of Count Statistic by Brand period', [], Metabase::DOMAIN_NAME, $locale).' 2',
            $this->buildDatasetQuery($this->getSqlQuerySecondary($defaultFields2['tag']), $defaultOrderStatus, $fields, $defaultFields2),
            'line',
            $collectionId,
        );

        $series = $this->formatSeries($card2->id);
        $series2 = $this->formatSeries($card4->id);

        $dashboardCard = $this->formatDashboardCard($card->id, $series, 0, 0, 24, 8, $card->id, $card2->id);
        $dashboardCard2 = $this->formatDashboardCard($card3->id, $series2, 0, 0, 24, 8, $card3->id, $card4->id);

        $this->embedDashboard($dashboard->id, $locale, ['invoiceDate1' => 'enabled', 'invoiceDate2' => 'enabled', 'brand_title' => 'enabled', 'orderStatus' => 'enabled'], [$dashboardCard]);
        $this->embedDashboard($dashboard2->id, $locale, ['invoiceDate1' => 'enabled', 'invoiceDate2' => 'enabled', 'brand_title' => 'enabled', 'orderStatus' => 'enabled'], [$dashboardCard2]);
    }

    private function getSqlQueryMain(string $param): string
    {
        return 'SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS TOTAL, 
            DATE_FORMAT(`order`.`invoice_date`,"%b") as DATE
            FROM `order` 
            INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`)
            LEFT JOIN `order_status` ON (`order_status`.`id` = `order`.`status_id`)
            LEFT JOIN `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
            INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
            INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
            WHERE 1=0 [[or {{brand}}]] and {{'.$param.'}} [[and {{orderStatus}}]]
            group by DATE_FORMAT(`order`.`invoice_date`, "%m"), DATE
            order by DATE_FORMAT(`order`.`invoice_date`, "%m")'
        ;
    }

    private function getSqlQuerySecondary(string $param): string
    {
        return 'SELECT SUM(order_product.quantity) AS TOTAL, 
                DATE_FORMAT(`order`.`invoice_date`,"%b") as DATE
                FROM `order` 
                INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                LEFT JOIN `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                LEFT JOIN `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
                INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
                WHERE 1=0 [[or {{brand}}]] and {{'.$param.'}} [[and {{orderStatus}}]]
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

    public function buildParameters(array $defaultOrderStatus, array $defaultFields = []): array
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
                'id' => $defaultFields['id'],
                'type' => 'date/relative',
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'invoiceDate',
                    ],
                ],
                'name' => $defaultFields['tag'],
                'slug' => $defaultFields['tag'],
                'default' => $defaultFields['date'],
            ],
            [
                'id' => $this->getUuidOrderStatus(),
                'type' => 'string/=',
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'orderStatus',
                    ],
                ],
                'name' => 'orderStatus',
                'slug' => 'orderStatus',
                'default' => $defaultOrderStatus,
            ],
        ];
    }

    /**
     * @throws MetabaseException
     */
    public function buildDatasetQuery(string $query, array $defaultOrderStatus, array $fields, array $defaultFields = []): array
    {
        $fieldBrandTitle = $this->metabaseAPIService->searchField($fields, 'title', 'brand_i18n');
        $fieldDate = $this->metabaseAPIService->searchField($fields, 'invoice_date', 'order');
        $fieldOrderStatus = $this->metabaseAPIService->searchField($fields, 'title', 'order_status_i18n');

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
                    $defaultFields['tag'] => [
                        'id' => $defaultFields['id'],
                        'name' => $defaultFields['tag'],
                        'display-name' => 'invoiceDate',
                        'type' => 'dimension',
                        'dimension' => [
                            'field',
                            $fieldDate,
                            null,
                        ],
                        'widget-type' => 'date/relative',
                        'default' => $defaultFields['date'],
                        'required' => true,
                    ],
                    'orderStatus' => [
                        'id' => $this->getUuidOrderStatus(),
                        'name' => 'orderStatus',
                        'display-name' => 'orderStatus',
                        'type' => 'dimension',
                        'dimension' => [
                            'field',
                            $fieldOrderStatus,
                            null,
                        ],
                        'widget-type' => 'string/=',
                        'default' => $defaultOrderStatus,
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
                        'invoiceDate1',
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
                        'invoiceDate2',
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
                'parameter_id' => $this->getUuidParamOrderStatus(),
                'card_id' => $cardsId[0],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'orderStatus',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->getUuidParamOrderStatus(),
                'card_id' => $cardsId[1],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'orderStatus',
                    ],
                ],
            ],
        ];
    }

    public function getDashboardParameters(array $defaultFields, string $locale): array
    {
        $translator = Translator::getInstance();

        return [
            [
                'name' => $translator?->trans('Brand Title', [], Metabase::DOMAIN_NAME, $locale),
                'slug' => 'brand_title',
                'id' => $this->uuidParamBrand,
                'type' => 'string/=',
                'sectionId' => 'string',
                'values_query_type' => 'search',
                'values_source_config' => [
                    'values' => $this->getValuesSourceConfigValuesBrandTitle($locale),
                ],
                'values_source_type' => 'static-list',
            ],
            [
                'name' => $translator?->trans('Period', [], Metabase::DOMAIN_NAME, $locale).' 1',
                'slug' => 'invoiceDate1',
                'id' => $this->getUuidParamDate1(),
                'type' => 'date/relative',
                'sectionId' => 'date',
                'default' => 'past1years',
            ],
            [
                'name' => $translator?->trans('Period', [], Metabase::DOMAIN_NAME, $locale).' 2',
                'slug' => 'invoiceDate2',
                'id' => $this->getUuidParamDate2(),
                'type' => 'date/relative',
                'sectionId' => 'date',
                'default' => 'thisyear',
            ],
            [
                'name' => $translator?->trans('orderStatus', [], Metabase::DOMAIN_NAME, $locale),
                'slug' => 'orderStatus',
                'id' => $this->getUuidParamOrderStatus(),
                'type' => 'string/=',
                'sectionId' => 'string',
                'default' => $this->getDefaultOrderStatus($locale),
                'values_query_type' => 'list',
                'values_source_config' => [
                    'values' => $this->getValuesSourceConfigValuesOrderStatus($locale),
                ],
                'values_source_type' => 'static-list',
            ],
        ];
    }

    private function getValuesSourceConfigValuesBrandTitle(string $locale): array
    {
        $brandTitles = [];

        $brands = BrandQuery::create()
            ->useBrandI18nQuery()
            ->filterByLocale($locale)
            ->endUse()
            ->find();

        foreach ($brands as $brand) {
            $brandTitles[] = $brand->setLocale($locale)->getTitle();
        }

        return $brandTitles;
    }
}
