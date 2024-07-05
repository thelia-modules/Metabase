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

class MonthlyRevenueStatisticMetabaseService extends AbstractMetabaseService
{
    /**
     * Create a table with sales revenue of store
     * Créer un tableau avec le chiffre d'affaires du magasin.
     *
     * @throws \JsonException
     * @throws MetabaseException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function generateStatisticMetabase(int $collectionId, array $fields, string $locale): void
    {
        $translator = Translator::getInstance();

        $endDate = new \DateTime('now');
        $startDate = new \DateTime('now');
        $startDate = $startDate->modify('-31 day');

        $defaultFields =
            [
                'idStartDate' => $this->getUuidDate1(),
                'tagStartDate' => 'invoiceDate1',
                'startDate' => $startDate->format('Y-m-d'),
                'idEndDate' => $this->getUuidDate2(),
                'tagEndDate' => 'invoiceDate2',
                'endDate' => $endDate->format('Y-m-d'),
            ]
        ;

        $dashboard = $this->generateDashboardMetabase(
            $translator?->trans('MonthlyRevenueDashboard', [], Metabase::DOMAIN_NAME, $locale),
            $translator?->trans('monthly revenue dashboard', [], Metabase::DOMAIN_NAME, $locale),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator?->trans('MonthlyRevenueCard', [], Metabase::DOMAIN_NAME, $locale),
            $translator?->trans('monthly revenue card', [], Metabase::DOMAIN_NAME, $locale),
            'line',
            $collectionId,
            $this->getSqlQueryMain($defaultFields['tagStartDate'], $defaultFields['tagEndDate']),
            $fields,
            $locale,
            $defaultFields
        );

        $card2 = $this->generateCardMetabase(
            $translator?->trans('MonthlyRevenueCardNumber', [], Metabase::DOMAIN_NAME, $locale),
            $translator?->trans('monthly revenue with number', [], Metabase::DOMAIN_NAME, $locale),
            'scalar',
            $collectionId,
            $this->getSqlQuerySecondary($defaultFields['tagStartDate'], $defaultFields['tagEndDate']),
            $fields,
            $locale,
            $defaultFields
        );

        $dashboardCard = $this->formatDashboardCard($card->id, [], 0, 0, 24, 5, $card->id, $card2->id);
        $dashboardCard2 = $this->formatDashboardCard($card2->id, [], 6, 0, 24, 3, $card->id, $card2->id);

        $this->embedDashboard(
            $dashboard->id,
            $locale,
            [
                'invoiceDate1' => 'enabled',
                'invoiceDate2' => 'enabled',
                'orderStatus' => 'enabled',
            ],
            [
                $dashboardCard,
                $dashboardCard2,
            ],
            $defaultFields
        );
    }

    private function getSqlQueryMain(string $invoiceDate1, string $invoiceDate2): string
    {
        return 'SELECT DATE_FORMAT(`order`.`invoice_date`,"%d %b %Y") as DATE,
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) + SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product_tax`.PROMO_AMOUNT,`order_product_tax`.AMOUNT))) - SUM(`order`.discount) AS TOTAL 
                    FROM `order` 
                    INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                    LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`)
                    LEFT JOIN `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                    LEFT JOIN `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                    WHERE (`order`.`invoice_date`>= {{'.$invoiceDate1.'}} AND `order`.`invoice_date`<= {{'.$invoiceDate2.'}})
                    AND ({{orderStatus}})
                    group by DATE_FORMAT(`order`.`invoice_date`, "%m %Y"), DATE
                    order by DATE_FORMAT(`order`.`invoice_date`, "%m %Y")'
        ;
    }

    private function getSqlQuerySecondary(string $invoiceDate1, string $invoiceDate2): string
    {
        return 'SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) + SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product_tax`.PROMO_AMOUNT,`order_product_tax`.AMOUNT))) - SUM(`order`.discount) AS TOTAL 
                    FROM `order`
                    INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                    LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`)
                    LEFT JOIN `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                    LEFT JOIN `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                    WHERE (`order`.`invoice_date`>= {{'.$invoiceDate1.'}} AND `order`.`invoice_date`<= {{'.$invoiceDate2.'}})
                    AND ({{orderStatus}})'
        ;
    }

    public function buildVisualizationSettings(): array
    {
        return [
            'graph.dimensions' => ['DATE'],
            'graph.metrics' => ['TOTAL'],
            'column_settings' => [
                '["name","TOTAL"]' => [
                    'suffix' => '€',
                    'number_separators' => ', ',
                ],
            ],
            'series_settings' => [
                'TOTAL' => [
                    'line.missing' => 'zero',
                ],
            ],
        ];
    }

    public function buildParameters(array $defaultOrderStatus, array $defaultFields = []): array
    {
        return [
            [
                'id' => $defaultFields['idStartDate'],
                'type' => 'date/single',
                'target' => ['dimension', ['template-tag', 'invoiceDate1']],
                'name' => $defaultFields['tagStartDate'],
                'slug' => $defaultFields['tagStartDate'],
                'default' => $defaultFields['startDate'],
            ],
            [
                'id' => $defaultFields['idEndDate'],
                'type' => 'date/single',
                'target' => ['dimension', ['template-tag', 'invoiceDate2']],
                'name' => $defaultFields['tagEndDate'],
                'slug' => $defaultFields['tagEndDate'],
                'default' => $defaultFields['endDate'],
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
        $fieldOrderStatus = $this->metabaseAPIService->searchField($fields, 'title', 'order_status_i18n');

        return [
            'database' => (int) Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY),
            'native' => [
                'template-tags' => [
                    $defaultFields['tagStartDate'] => [
                        'id' => $defaultFields['idStartDate'],
                        'name' => $defaultFields['tagStartDate'],
                        'display-name' => 'invoiceDate1',
                        'type' => 'date',
                        'widget-type' => 'date/single',
                        'default' => $defaultFields['startDate'],
                    ],
                    $defaultFields['tagEndDate'] => [
                        'id' => $defaultFields['idEndDate'],
                        'name' => $defaultFields['tagEndDate'],
                        'display-name' => 'invoiceDate2',
                        'type' => 'date',
                        'widget-type' => 'date/single',
                        'default' => $defaultFields['endDate'],
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
                    'variable',
                    [
                        'template-tag',
                        'invoiceDate1',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->getUuidParamDate1(),
                'card_id' => $cardsId[1],
                'target' => [
                    'variable',
                    [
                        'template-tag',
                        'invoiceDate1',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->getUuidParamDate2(),
                'card_id' => $cardsId[0],
                'target' => [
                    'variable',
                    [
                        'template-tag',
                        'invoiceDate2',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->getUuidParamDate2(),
                'card_id' => $cardsId[1],
                'target' => [
                    'variable',
                    [
                        'template-tag',
                        'invoiceDate2',
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
                'name' => $translator?->trans('Date Start', [], Metabase::DOMAIN_NAME, $locale),
                'slug' => 'invoiceDate1',
                'id' => $this->getUuidParamDate1(),
                'type' => 'date/single',
                'sectionId' => 'date',
                'default' => $defaultFields['startDate'],
            ],
            [
                'name' => $translator?->trans('Date End', [], Metabase::DOMAIN_NAME, $locale),
                'slug' => 'invoiceDate2',
                'id' => $this->getUuidParamDate2(),
                'type' => 'date/single',
                'sectionId' => 'date',
                'default' => $defaultFields['endDate'],
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
