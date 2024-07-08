<?php

namespace Metabase\Service;

use Metabase\Exception\MetabaseException;
use Metabase\Metabase;
use Metabase\Service\API\MetabaseAPIService;
use Metabase\Service\Base\AbstractMetabaseService;
use PDO;
use Propel\Runtime\Propel;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Category;
use Thelia\Model\CategoryQuery;
use Thelia\Model\LangQuery;
use Thelia\Model\Map\CategoryTableMap;

class CategoryStatisticMetabaseService extends AbstractMetabaseService
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
     * @param int $collectionId
     * @param array $fields
     * @param string $locale
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
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
            $translator->trans('Dashboard Sales Category', [], Metabase::DOMAIN_NAME, $locale),
            $translator->trans('Sales Statistic by Category dashboard', [], Metabase::DOMAIN_NAME, $locale),
            $collectionId
        );

        $dashboard2 = $this->generateDashboardMetabase(
            $translator->trans('Dashboard Count Category', [], Metabase::DOMAIN_NAME, $locale),
            $translator->trans('Count Statistic by Category dashboard', [], Metabase::DOMAIN_NAME, $locale),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator->trans('CategoriesSalesCard_', [], Metabase::DOMAIN_NAME, $locale).'1',
            $translator->trans('Card of Sales Statistic by Category period', [], Metabase::DOMAIN_NAME, $locale).' 1',
            'line',
            $collectionId,
            $this->getSqlQueryMain($defaultFields1['tag']),
            $fields,
            $locale,
            $defaultFields1
        );

        $card2 = $this->generateCardMetabase(
            $translator->trans('CategoriesSalesCard_', [], Metabase::DOMAIN_NAME, $locale).'2',
            $translator->trans('Card of Sales Statistic by Category period', [], Metabase::DOMAIN_NAME, $locale).' 2',
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
            $translator->trans('CategoriesCard_', [], Metabase::DOMAIN_NAME, $locale).'1',
            $translator->trans('Card of Count Statistic by Category period', [], Metabase::DOMAIN_NAME, $locale).' 1',
            $this->buildDatasetQuery($this->getSqlQuerySecondary($defaultFields1['tag']), $defaultOrderStatus, $fields, $defaultFields1),
            'line',
            $collectionId,
        );

        $card4 = $this->generateCustomCardMetabase(
            $this->buildCustomVisualizationSettings(),
            $this->buildParameters($defaultOrderStatus, $defaultFields2),
            $translator->trans('CategoriesCard_', [], Metabase::DOMAIN_NAME, $locale).'2',
            $translator->trans('Card of Count Statistic by Category period', [], Metabase::DOMAIN_NAME, $locale).' 2',
            $this->buildDatasetQuery($this->getSqlQuerySecondary($defaultFields2['tag']), $defaultOrderStatus, $fields, $defaultFields2),
            'line',
            $collectionId,
        );

        $series = $this->formatSeries($card2->id);
        $series2 = $this->formatSeries($card4->id);

        $dashboardCard = $this->formatDashboardCard($card->id, $series, 0, 0, 24, 8, $card->id, $card2->id);
        $dashboardCard2 = $this->formatDashboardCard($card3->id, $series2, 0, 0, 24, 8, $card3->id, $card4->id);

        $this->embedDashboard($dashboard->id, $locale, ['invoiceDate1' => 'enabled', 'invoiceDate2' => 'enabled', 'category_title' => 'enabled', 'orderStatus' => 'enabled'], [$dashboardCard]);
        $this->embedDashboard($dashboard2->id, $locale, ['invoiceDate1' => 'enabled', 'invoiceDate2' => 'enabled', 'category_title' => 'enabled', 'orderStatus' => 'enabled'], [$dashboardCard2]);
    }

    private function getSqlQueryMain(string $param): string
    {
        return 'select SUM(ROUND(order_product.quantity * IF(order_product.was_in_promo = 1, order_product.promo_price, order_product.price), 2) ) AS TOTAL,
                DATE_FORMAT(`order`.`invoice_date`, "%b") as DATE
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                join `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where 1=0 [[or {{category}}]] and {{'.$param.'}} [[and {{orderStatus}}]]
                group by DATE_FORMAT(`order`.`invoice_date`, "%m"), DATE
                order by DATE_FORMAT(`order`.`invoice_date`, "%m")'
        ;
    }

    private function getSqlQuerySecondary(string $param): string
    {
        return 'select SUM(`order_product`.quantity) as TOTAL,
                DATE_FORMAT(`order`.`invoice_date`, "%b") as DATE
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                join `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where 1=0 [[or {{category}}]] and {{'.$param.'}} [[and {{orderStatus}}]]
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

    public function buildParameters(array $defaultOrderStatus, array $defaultFields = []): array
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
        $fieldCategoryTitle = $this->metabaseAPIService->searchField($fields, 'title', 'category_i18n');
        $fieldDate = $this->metabaseAPIService->searchField($fields, 'invoice_date', 'order');
        $fieldOrderStatus = $this->metabaseAPIService->searchField($fields, 'title', 'order_status_i18n');

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
                'name' => $translator?->trans('Category Title', [], Metabase::DOMAIN_NAME, $locale),
                'slug' => 'category_title',
                'id' => $this->uuidParamCategory,
                'type' => 'string/=',
                'sectionId' => 'string',
                'values_query_type' => 'list',
                'values_source_config' => [
                    'values' => $this->getValuesSourceConfigValuesCategoryTitle($locale),
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

    private function getValuesSourceConfigValuesCategoryTitle(string $locale): array
    {
        $connection = Propel::getWriteConnection(CategoryTableMap::DATABASE_NAME);

        $sql = "WITH RECURSIVE CategoryPaths AS (
                    SELECT c.id, c.parent, ci.title AS path
                    FROM category c
                    JOIN category_i18n ci ON c.id = ci.id
                    WHERE c.parent = 0 AND ci.locale = :locale
                    UNION ALL
    
                    SELECT c.id, c.parent, CONCAT(cp.path, ' > ', ci.title) AS path
                    FROM category c
                    JOIN  CategoryPaths cp ON c.parent = cp.id
                    JOIN  category_i18n ci ON c.id = ci.id
                    WHERE ci.locale = :locale
                )
    
                SELECT cp.path
                FROM CategoryPaths cp
                ORDER BY cp.path;
            ";

        $statement = $connection->prepare($sql);
        $statement->bindValue(':locale', $locale);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}
