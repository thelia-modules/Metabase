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

class StatisticCategoryService extends AbstractMetabaseService
{
    private string $uuidCategory;
    private string $uuidParamCategory;

    public function __construct(protected MetabaseAPIService $metabaseAPIService)
    {
        parent::__construct($metabaseAPIService);

        $this->uuidCategory = uniqid('', true);
        $this->uuidParamCategory = uniqid('', true);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     */
    public function generateStatisticMetabase(int $collectionId, array $fields): void
    {
        $translator = Translator::getInstance();

        $defaultFields = ['date' => 'past1years'];
        $defaultFields2 = ['date' => 'thisyear'];

        $dashboard = $this->generateDashboardMetabase(
            $translator->trans('Dashboard Sales Category', [], Metabase::DOMAIN_NAME),
            $translator->trans('Sales Statistic by Category dashboard', [], Metabase::DOMAIN_NAME),
            $collectionId
        );

        $dashboard2 = $this->generateDashboardMetabase(
            $translator->trans('Dashboard Count Category', [], Metabase::DOMAIN_NAME),
            $translator->trans('Count Statistic by Category dashboard', [], Metabase::DOMAIN_NAME),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator->trans('CategoriesSalesCard_', [], Metabase::DOMAIN_NAME).'1',
            $translator->trans('Card of Sales Statistic by Category period', [], Metabase::DOMAIN_NAME).' 1',
            'line',
            $collectionId,
            $this->getSqlQueryMain(),
            $fields,
            $defaultFields
        );

        $card2 = $this->generateCardMetabase(
            $translator->trans('CategoriesSalesCard_', [], Metabase::DOMAIN_NAME).'2',
            $translator->trans('Card of Sales Statistic by Category period', [], Metabase::DOMAIN_NAME).' 2',
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
            $translator->trans('CategoriesCard_', [], Metabase::DOMAIN_NAME).'1',
            $translator->trans('Card of Count Statistic by Category period', [], Metabase::DOMAIN_NAME).' 1',
            $this->buildDatasetQuery($this->getSqlQuerySecondary(), $defaultOrderType, $fields, $defaultFields),
            'line',
            $collectionId,
        );

        $card4 = $this->generateCustomCardMetabase(
            $this->buildCustomVisualizationSettings(),
            $this->buildParameters($defaultOrderType, $defaultFields2),
            $translator->trans('CategoriesCard_', [], Metabase::DOMAIN_NAME).'2',
            $translator->trans('Card of Count Statistic by Category period', [], Metabase::DOMAIN_NAME).' 2',
            $this->buildDatasetQuery($this->getSqlQuerySecondary(), $defaultOrderType, $fields, $defaultFields2),
            'line',
            $collectionId,
        );

        $series = $this->formatSeries($card2->id);
        $series2 = $this->formatSeries($card2->id);

        $dashboardCard = $this->formatDashboardCard($card->id, $series, 0, 0, 24, 8, $card->id, $card2->id);
        $dashboardCard2 = $this->formatDashboardCard($card->id, $series2, 0, 0, 24, 8, $card3->id, $card4->id);

        $this->embedDashboard($dashboard->id, ['date_1' => 'enabled', 'date_2' => 'enabled', 'category_reference' => 'enabled', 'orderType' => 'enabled'], [$dashboardCard]);
        $this->embedDashboard($dashboard2->id, ['date_1' => 'enabled', 'date_2' => 'enabled', 'category_reference' => 'enabled', 'orderType' => 'enabled'], [$dashboardCard2]);

        $this->publishDashboard($dashboard->id);
        $this->publishDashboard($dashboard2->id);
    }

    private function getSqlQueryMain(): string
    {
        return 'select SUM(ROUND(order_product.quantity * IF(order_product.was_in_promo = 1, order_product.promo_price, order_product.price), 2) ) AS TOTAL,
                DATE_FORMAT(`order`.`invoice_date`, "%b") as DATE
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where 1=0 [[or {{category}}]] and {{date}} [[and {{orderType}}]]
                group by DATE_FORMAT(`order`.`invoice_date`, "%m"), DATE
                order by DATE_FORMAT(`order`.`invoice_date`, "%m")'
        ;
    }

    private function getSqlQuerySecondary(): string
    {
        return 'select SUM(`order_product`.quantity) as TOTAL,
                DATE_FORMAT(`order`.`invoice_date`, "%b") as DATE
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where 1=0 [[or {{category}}]] and {{date}} [[and {{orderType}}]]
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

    private function buildCustomVisualizationSettings(): array
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
                'id' => $this->uuidCategory,
                'type' => 'string/=',
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'category',
                    ],
                ],
                'name' => 'Category',
                'slug' => 'category',
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
        $fieldCategoryTitle = $this->metabaseAPIService->searchField($fields, 'title', 'category_i18n');
        $fieldDate = $this->metabaseAPIService->searchField($fields, 'invoice_date', 'order');
        $fieldOrderType = $this->metabaseAPIService->searchField($fields, 'status_id', 'order');

        return [
            'database' => (int) Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY),
            'native' => [
                'template-tags' => [
                    'category' => [
                        'id' => $this->uuidCategory,
                        'name' => 'category',
                        'display-name' => 'Category',
                        'type' => 'dimension',
                        'dimension' => [
                            'field',
                            $fieldCategoryTitle,
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
                        'default' => 'thisyear',
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
                'parameter_id' => $this->uuidParamCategory,
                'card_id' => $cardsId[0],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'category',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->uuidParamCategory,
                'card_id' => $cardsId[1],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'category',
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
                'name' => 'Category Title',
                'slug' => 'category_title',
                'id' => $this->uuidParamCategory,
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
