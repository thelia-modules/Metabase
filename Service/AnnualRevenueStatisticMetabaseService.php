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

class AnnualRevenueStatisticMetabaseService extends AbstractMetabaseService
{
    /**
     * Create a table with sales revenue of store.
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
            $translator?->trans('AnnualRevenueDashboard', [], Metabase::DOMAIN_NAME, $locale),
            $translator?->trans('annual revenue dashboard', [], Metabase::DOMAIN_NAME, $locale),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator?->trans('AnnualRevenueCard_', [], Metabase::DOMAIN_NAME, $locale).'1',
            $translator?->trans('annual revenue card', [], Metabase::DOMAIN_NAME, $locale).' 1',
            'line',
            $collectionId,
            $this->getSqlQueryMain($defaultFields1['tag']),
            $fields,
            $locale,
            $defaultFields1
        );

        $card2 = $this->generateCardMetabase(
            $translator?->trans('AnnualRevenueCard_', [], Metabase::DOMAIN_NAME, $locale).'2',
            $translator?->trans('annual revenue card', [], Metabase::DOMAIN_NAME, $locale).' 2',
            'line',
            $collectionId,
            $this->getSqlQueryMain($defaultFields2['tag']),
            $fields,
            $locale,
            $defaultFields2
        );

        $card3 = $this->generateCardMetabase(
            $translator?->trans('AnnualRevenueCardNumber_', [], Metabase::DOMAIN_NAME, $locale).'1',
            $translator?->trans('annual revenue card number', [], Metabase::DOMAIN_NAME, $locale).' 1',
            'scalar',
            $collectionId,
            $this->getSqlQuerySecondary($defaultFields1['tag']),
            $fields,
            $locale,
            $defaultFields1
        );

        $card4 = $this->generateCardMetabase(
            $translator?->trans('AnnualRevenueCardNumber_', [], Metabase::DOMAIN_NAME, $locale).'2',
            $translator?->trans('annual revenue card number', [], Metabase::DOMAIN_NAME, $locale).' 2',
            'scalar',
            $collectionId,
            $this->getSqlQuerySecondary($defaultFields2['tag']),
            $fields,
            $locale,
            $defaultFields2
        );

        $series = $this->formatSeries($card2->id);

        $dashboardCard = $this->formatDashboardCard($card->id, $series, 0, 0, 24, 5, $card->id, $card2->id, $card3->id, $card4->id);
        $dashboardCard3 = $this->formatDashboardCard($card3->id, [], 6, 0, 12, 3, $card->id, $card2->id, $card3->id, $card4->id);
        $dashboardCard4 = $this->formatDashboardCard($card4->id, [], 6, 13, 12, 3, $card->id, $card2->id, $card3->id, $card4->id);

        $this->embedDashboard(
            $dashboard->id,
            $locale,
            [
                'invoiceDate1' => 'enabled',
                'invoiceDate2' => 'enabled',
                'orderStatus' => 'enabled',
            ],
            [$dashboardCard, $dashboardCard3, $dashboardCard4]
        );
    }

    private function getSqlQueryMain(string $param): string
    {
        return 'SELECT q1.DATE1 as DATE, (q1.CA - q2.DISCOUNT) as TOTAL
                        FROM (
                            SELECT DATE_FORMAT(`order`.`invoice_date`, "%b") as DATE1,
                            SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) as CA
                            FROM `order`
                            INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`)
                            LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`)
                            LEFT JOIN `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                            LEFT JOIN `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                            WHERE {{'.$param.'}} AND {{orderStatus}}
                            group by DATE_FORMAT(`order`.`invoice_date`, "%m"), DATE1
                            order by DATE_FORMAT(`order`.`invoice_date`, "%m")
                            ) as q1,
                            (
                            SELECT DATE_FORMAT(`order`.`invoice_date`, "%b") as DATE2, SUM(`order`.`discount`) AS DISCOUNT
                            FROM `order`
                            LEFT JOIN `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                            LEFT JOIN `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                            where {{'.$param.'}} AND {{orderStatus}}
                            group by DATE_FORMAT(`order`.`invoice_date`, "%m"), DATE2
                            order by DATE_FORMAT(`order`.`invoice_date`, "%m")
                        ) as q2
                        where q1.DATE1 = q2.DATE2';
    }

    private function getSqlQuerySecondary(string $param): string
    {
        return 'SELECT (q1.CA - q2.DISCOUNT) as TOTAL
                        FROM (
                            SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS CA
                            FROM `order`
                            INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`)
                            LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`)
                            LEFT JOIN `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                            LEFT JOIN `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                            WHERE {{'.$param.'}} AND {{orderStatus}}
                            ) as q1,
                            (
                            SELECT SUM(`order`.`discount`) AS DISCOUNT
                            FROM `order`
                            LEFT JOIN `order_status` ON (`order_status`.`id` = `order`.`status_id`)
                            LEFT JOIN `order_status_i18n` ON (`order_status_i18n`.`id` = `order_status`.`id`)
                            where {{'.$param.'}} AND {{orderStatus}}
                            ) as q2';
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
        ];
    }

    public function buildParameters(array $defaultOrderStatus, array $defaultFields = []): array
    {
        return [
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
        $fieldDate = $this->metabaseAPIService->searchField($fields, 'invoice_date', 'order');
        $fieldOrderStatus = $this->metabaseAPIService->searchField($fields, 'title', 'order_status_i18n');

        return [
            'database' => (int) Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY),
            'native' => [
                'template-tags' => [
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
                'parameter_id' => $this->getUuidParamDate1(),
                'card_id' => $cardsId[2],
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
                'card_id' => $cardsId[3],
                'target' => [
                    'dimension',
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
            [
                'parameter_id' => $this->getUuidParamOrderStatus(),
                'card_id' => $cardsId[2],
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
                'card_id' => $cardsId[3],
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
}
