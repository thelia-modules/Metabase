<?php

namespace Metabase\Service;

use Metabase\Exception\MetabaseException;
use Metabase\Metabase;
use Metabase\Service\Base\AbstractMetabaseService;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Core\Translation\Translator;

class BestSellerStatisticMetabaseService extends AbstractMetabaseService
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws MetabaseException
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function generateStatisticMetabase(int $collectionId, array $fields, string $locale): void
    {
        $translator = Translator::getInstance();

        $dashboard = $this->generateDashboardMetabase(
            $translator->trans('Dashboard Best Seller', [], Metabase::DOMAIN_NAME, $locale),
            $translator->trans('Best Seller dashboard', [], Metabase::DOMAIN_NAME, $locale),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator->trans('BestSellerCard', [], Metabase::DOMAIN_NAME, $locale),
            $translator->trans('Best Seller card', [], Metabase::DOMAIN_NAME, $locale),
            'table',
            $collectionId,
            $this->getSqlQueryMain($locale),
            $fields,
            $locale
        );

        $dashboardCard = $this->formatDashboardCard($card->id, [], 0, 0, 24, 8, $card->id);

        $this->embedDashboard(
            $dashboard->id,
            $locale,
            [
                'date' => 'enabled',
                'orderStatus' => 'enabled',
            ],
            [$dashboardCard]
        );
    }

    private function getSqlQueryMain(string $locale): string
    {
        $translator = Translator::getInstance();

        return 'SELECT SUM(`order_product`.`QUANTITY`) AS "'.$translator?->trans('TOTAL SOLD', [], Metabase::DOMAIN_NAME, $locale, $locale).'",
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS "'.$translator?->trans('TOTAL HT', [], Metabase::DOMAIN_NAME, $locale).'",
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) + 
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product_tax`.PROMO_AMOUNT,`order_product_tax`.AMOUNT))) -
                    SUM(`order`.`discount`) AS "'.$translator?->trans('TOTAL TTC', [], Metabase::DOMAIN_NAME, $locale).'",
                    `order_product`.`title` AS "'.$translator?->trans('PRODUCT TITLE', [], Metabase::DOMAIN_NAME, $locale).'",
                    `order_product`.`product_ref` AS "'.$translator?->trans('PRODUCT REFERENCE', [], Metabase::DOMAIN_NAME, $locale).'"
                    FROM `order`
                    INNER JOIN `order_product` ON `order`.`id`=`order_product`.`order_id`
                    INNER JOIN `order_product_tax` ON `order_product`.`id`=`order_product_tax`.`order_product_id`
                    LEFT JOIN `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                    LEFT JOIN `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                    WHERE {{orderStatus}} [[AND {{date}}]]
                    GROUP BY `order_product`.`title`, `order_product`.`product_ref`
                    ORDER BY SUM(`order_product`.`quantity`) DESC'
        ;
    }

    public function buildVisualizationSettings(): array
    {
        return [
            'graph.dimensions' => ['Date'],
            'graph.series_order_dimension' => null,
            'graph.series_order' => null,
            'column_settings' => [
                '["name","total_ht"]' => [
                    'suffix' => '€',
                    'number_separators' => ', ',
                ],
                '["name","tax"]' => [
                    'suffix' => '€',
                    'number_separators' => ', ',
                ],
            ],
            'graph.metrics' => ['category'],
        ];
    }

    public function buildParameters(array $defaultOrderStatus, array $defaultFields = []): array
    {
        return [
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
                'name' => 'Date',
                'slug' => 'date',
                'default' => 'thisyear',
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
                'name' => 'OrderStatus',
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
        $fieldDate = $this->metabaseAPIService->searchField($fields, 'created_at', 'order');
        $fieldOrderStatus = $this->metabaseAPIService->searchField($fields, 'title', 'order_status_i18n');

        return [
            'database' => (int) Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY),
            'native' => [
                'template-tags' => [
                    'date' => [
                        'id' => $this->getUuidDate1(),
                        'name' => 'date',
                        'display-name' => 'Date',
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
                    'orderStatus' => [
                        'id' => $this->getUuidOrderStatus(),
                        'name' => 'orderStatus',
                        'display-name' => 'OrderStatus',
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
                        'date',
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
        ];
    }

    public function getDashboardParameters(array $defaultFields, string $locale): array
    {
        $translator = Translator::getInstance();

        return [
            [
                'name' => 'Date',
                'slug' => 'date',
                'id' => $this->getUuidParamDate1(),
                'type' => 'date/all-options',
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
}
