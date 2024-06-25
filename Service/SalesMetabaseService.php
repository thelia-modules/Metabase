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

class SalesMetabaseService extends AbstractMetabaseService
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
    public function generateStatisticMetabase(int $collectionId, array $fields): void
    {
        $translator = Translator::getInstance();

        $defaultFields1 =
            [
                'date' => 'past1years',
                'fields' => $fields,
            ]
        ;

        $defaultFields2 =
            [
                'date' => 'thisyear',
                'fields' => $fields,
            ]
        ;

        $dashboard = $this->generateDashboardMetabase(
            $translator->trans('SalesDashboard', [], Metabase::DOMAIN_NAME),
            $translator->trans('sales of the store', [], Metabase::DOMAIN_NAME),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator->trans('SalesCard', [], Metabase::DOMAIN_NAME).'1',
            $translator->trans('card with sales', [], Metabase::DOMAIN_NAME),
            'line',
            $collectionId,
            $this->getSqlQueryMain(),
            $fields,
            $defaultFields1
        );

        $card2 = $this->generateCardMetabase(
            $translator->trans('SalesCard', [], Metabase::DOMAIN_NAME).'2',
            $translator->trans('card with sales', [], Metabase::DOMAIN_NAME),
            'line',
            $collectionId,
            $this->getSqlQueryMain(),
            $fields,
            $defaultFields1
        );

        $card3 = $this->generateCardMetabase(
            $translator->trans('Sale Number', [], Metabase::DOMAIN_NAME).'1',
            $translator->trans('card with the sale number', [], Metabase::DOMAIN_NAME),
            'scalar',
            $collectionId,
            $this->getSqlQuerySecondary(),
            $fields,
            $defaultFields1
        );

        $card4 = $this->generateCardMetabase(
            $translator->trans('Sale Number', [], Metabase::DOMAIN_NAME).'2',
            $translator->trans('card with the sale number', [], Metabase::DOMAIN_NAME),
            'scalar',
            $collectionId,
            $this->getSqlQuerySecondary(),
            $fields,
            $defaultFields2
        );

        $series = $this->formatSeries($card2->id);

        $dashboardCard = $this->formatDashboardCard($card->id, $series, 0, 0, 24, 5, $card->id, $card2->id, $card3->id, $card4->id);
        // $dashboardCard2 = $this->formatDashboardCard($card2->id, [], 0, 0, 24, 5, $card->id, $card2->id, $card3->id, $card4->id);
        $dashboardCard3 = $this->formatDashboardCard($card3->id, [], 0, 0, 12, 5, $card->id, $card2->id, $card3->id, $card4->id);
        $dashboardCard4 = $this->formatDashboardCard($card4->id, [], 0, 0, 12, 5, $card->id, $card2->id, $card3->id, $card4->id);

        $this->generateDashboardCard($dashboard->id, [$dashboardCard, $dashboardCard3, $dashboardCard4]);

        $this->embedDashboard(
            $dashboard->id,
            [
                'date_1' => 'enabled',
                'date_2' => 'enabled',
                'orderType' => 'enabled',
            ],
            []
        );

        $this->publishDashboard($dashboard->id);
    }

    private function getSqlQueryMain(): string
    {
        return 'SELECT q1.DATE1 as DATE, (q1.CA - q2.DISCOUNT) as TOTAL
                        FROM (
                            SELECT DATE_FORMAT(`order`.`invoice_date`, "%b") as DATE1,
                            SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) as CA
                            FROM `order`
                            INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`)
                            LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`)
                            WHERE {{start}} AND {{orderType}}
                            group by DATE_FORMAT(`order`.`invoice_date`, "%m"), DATE1
                            order by DATE_FORMAT(`order`.`invoice_date`, "%m")
                            ) as q1,
                            (
                            SELECT DATE_FORMAT(`order`.`invoice_date`, "%b") as DATE2, SUM(`order`.`discount`) AS DISCOUNT
                            FROM `order`
                            where {{start}} AND {{orderType}}
                            group by DATE_FORMAT(`order`.`invoice_date`, "%m"), DATE2
                            order by DATE_FORMAT(`order`.`invoice_date`, "%m")
                        ) as q2
                        where q1.DATE1 = q2.DATE2';
    }

    private function getSqlQuerySecondary(): string
    {
        return 'SELECT (q1.CA - q2.DISCOUNT) as TOTAL
                        FROM (
                            SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS CA
                            FROM `order`
                            INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`)
                            LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`)
                            WHERE {{start}} AND {{orderType}}
                            ) as q1,
                            (
                            SELECT SUM(`order`.`discount`) AS DISCOUNT
                            FROM `order`
                            where {{start}} AND {{orderType}}
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

    public function buildParameters(array $defaultOrderType, array $defaultFields = []): array
    {
        return [
            [
                'id' => $this->getUuidDate1(),
                'type' => 'date/relative',
                'target' => ['dimension', ['template-tag', 'start']],
                'name' => 'Start',
                'slug' => 'start',
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
        $fieldDate = $this->metabaseAPIService->searchField($fields, 'invoice_date', 'order');
        $fieldOrderType = $this->metabaseAPIService->searchField($fields, 'status_id', 'order');

        return [
            'database' => (int) Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY),
            'native' => [
                'template-tags' => [
                    'start' => [
                        'id' => $this->getUuidDate1(),
                        'name' => 'start',
                        'display-name' => 'Start',
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
                        'start',
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
                        'start',
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
                        'start',
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
                        'start',
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
            [
                'parameter_id' => $this->getUuidParamOrderType(),
                'card_id' => $cardsId[2],
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
                'card_id' => $cardsId[3],
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
                'name' => 'Date 1',
                'slug' => 'date_1',
                'id' => $this->getUuidParamDate1(),
                'type' => 'date/all-options',
                'sectionId' => 'start',
                'default' => 'thisyear',
            ],
            [
                'name' => 'Date 2',
                'slug' => 'date_2',
                'id' => $this->getUuidParamDate2(),
                'type' => 'date/all-options',
                'sectionId' => 'start',
                'default' => 'past1years',
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
