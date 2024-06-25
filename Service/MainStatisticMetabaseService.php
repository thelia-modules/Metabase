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

class MainStatisticMetabaseService extends AbstractMetabaseService
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
    public function generateStatisticMetabase(int $collectionId, array $fields): void
    {
        $translator = Translator::getInstance();

        $endDate = new \DateTime('now');
        $startDate = new \DateTime('now');
        $startDate = $startDate->modify('-31 day');

        $defaultFields =
            [
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ]
        ;

        $dashboard = $this->generateDashboardMetabase(
            $translator->trans('MainDashboard', [], Metabase::DOMAIN_NAME),
            $translator->trans('main dashboard', [], Metabase::DOMAIN_NAME),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator->trans('MainCard', [], Metabase::DOMAIN_NAME),
            $translator->trans('main card', [], Metabase::DOMAIN_NAME),
            'line',
            $collectionId,
            $this->getSqlQueryMain(),
            $fields,
            $defaultFields
        );

        $card2 = $this->generateCardMetabase(
            $translator->trans('MainCardNumber', [], Metabase::DOMAIN_NAME),
            $translator->trans('main card with number', [], Metabase::DOMAIN_NAME),
            'scalar',
            $collectionId,
            $this->getSqlQuerySecondary(),
            $fields,
            $defaultFields
        );

        $dashboardCard = $this->formatDashboardCard($card->id, [], 0, 0, 24, 5, $card->id, $card2->id);

        $dashboardCard2 = $this->formatDashboardCard($card2->id, [], 6, 0, 24, 3, $card->id, $card2->id);

        $this->generateDashboardCard($dashboard->id, [$dashboardCard, $dashboardCard2]);

        $this->embedDashboard($dashboard->id, ['date' => 'enabled'], $defaultFields);

        $this->publishDashboard($dashboard->id);
    }

    private function getSqlQueryMain(): string
    {
        return 'SELECT DATE_FORMAT(`order`.`invoice_date`,"%d %b %Y") as DATE,
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) + SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product_tax`.PROMO_AMOUNT,`order_product_tax`.AMOUNT))) - SUM(`order`.discount) AS TOTAL 
                    FROM `order` 
                    INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                    LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`) 
                    WHERE (`order`.`invoice_date`>={{start}} AND `order`.`invoice_date`<={{end}}) AND {{orderType}} 
                    group by DATE_FORMAT(`order`.`invoice_date`, "%m %Y"), DATE
                    order by DATE_FORMAT(`order`.`invoice_date`, "%m %Y")'
        ;
    }

    private function getSqlQuerySecondary(): string
    {
        return 'SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) + SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product_tax`.PROMO_AMOUNT,`order_product_tax`.AMOUNT))) - SUM(`order`.discount) AS TOTAL 
                    FROM `order`
                    INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                    LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`) 
                    WHERE (`order`.`invoice_date`>={{start}} AND `order`.`invoice_date`<={{end}}) AND {{orderType}}'
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

    public function buildParameters(array $defaultOrderType, array $defaultFields = []): array
    {
        return [
            [
                'id' => $this->getUuidDate1(),
                'type' => 'date/single',
                'target' => ['dimension', ['template-tag', 'start']],
                'name' => 'Start',
                'slug' => 'start',
                'default' => $defaultFields['startDate'],
            ],
            [
                'id' => $this->getUuidDate2(),
                'type' => 'date/single',
                'target' => ['dimension', ['template-tag', 'end']],
                'name' => 'End',
                'slug' => 'end',
                'default' => $defaultFields['endDate'],
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
        $fieldOrderType = $this->metabaseAPIService->searchField($fields, 'status_id', 'order');

        return [
            'database' => (int) Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY),
            'native' => [
                'template-tags' => [
                    'start' => [
                        'id' => $this->getUuidDate1(),
                        'name' => 'start',
                        'display-name' => 'Start',
                        'type' => 'date',
                        'default' => $defaultFields['startDate'],
                        'required' => true,
                    ],
                    'end' => [
                        'id' => $this->getUuidDate2(),
                        'name' => 'end',
                        'display-name' => 'End',
                        'type' => 'date',
                        'default' => $defaultFields['endDate'],
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
                    'variable',
                    [
                        'template-tag',
                        'start',
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
                        'start',
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
                        'end',
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
                        'end',
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
                'name' => 'Date Start',
                'slug' => 'start',
                'id' => $this->getUuidParamDate1(),
                'type' => 'date/single',
                'sectionId' => 'date',
                'default' => $defaultFields['startDate'],
            ],
            [
                'name' => 'Date End',
                'slug' => 'end',
                'id' => $this->getUuidParamDate2(),
                'type' => 'date/single',
                'sectionId' => 'date',
                'default' => $defaultFields['endDate'],
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
