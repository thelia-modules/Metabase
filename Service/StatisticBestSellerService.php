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

class StatisticBestSellerService extends AbstractMetabaseService
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws MetabaseException
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function generateStatisticMetabase(int $collectionId, array $fields): void
    {
        $translator = Translator::getInstance();

        $dashboard = $this->generateDashboardMetabase(
            $translator->trans('Dashboard Best Seller', [], Metabase::DOMAIN_NAME),
            $translator->trans('Best Seller dashboard', [], Metabase::DOMAIN_NAME),
            $collectionId
        );

        $card = $this->generateCardMetabase(
            $translator->trans('BestSellerCard', [], Metabase::DOMAIN_NAME),
            $translator->trans('Best Seller card', [], Metabase::DOMAIN_NAME),
            'table',
            $collectionId,
            $this->getSqlQueryMain(),
            $fields
        );

        $dashboardCard = $this->formatDashboardCard($card->id, [], 0, 0, 24, 8, $card->id);

        $this->generateDashboardCard($dashboard->id, [$dashboardCard]);

        $this->embedDashboard(
            $dashboard->id,
            [
                'start' => 'enabled',
                'end' => 'enabled',
                'orderType' => 'enabled',
            ]
        );

        $this->publishDashboard($dashboard->id);
    }

    private function getSqlQueryMain(): string
    {
        return 'SELECT SUM(`order_product`.`quantity`) AS TOTAL_SOLD,
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS TOTAL_HT,
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product_tax`.PROMO_AMOUNT,`order_product_tax`.AMOUNT))) AS TAX,
                    `order_product`.`title` AS TITLE,
                    `order_product`.`product_ref` AS PRODUCT_REFERENCE, 
                    `order_product`.`product_sale_elements_id` AS PSE
                    FROM `order`
                    INNER JOIN `order_product` ON `order`.`id`=`order_product`.`order_id`
                    INNER JOIN `order_product_tax` ON `order_product`.`id`=`order_product_tax`.`order_product_id`
                    [[WHERE {{date}}]]
                    GROUP BY title, PRODUCT_REFERENCE, PSE
                    ORDER BY TOTAL_SOLD DESC'
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

    public function buildParameters(array $defaultOrderType, array $defaultFields = []): array
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
                'default' => 'past1years',
            ],
        ];
    }

    /**
     * @throws MetabaseException
     */
    public function buildDatasetQuery(string $query, array $defaultOrderType, array $fields, array $defaultFields = []): array
    {
        $fieldDate = $this->metabaseAPIService->searchField($fields, 'created_at', 'order');

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
                        'default' => 'past1years',
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
        ];
    }

    public function getDashboardParameters(array $defaultFields): array
    {
        return [
            [
                'name' => 'Date',
                'slug' => 'date',
                'id' => $this->getUuidParamDate1(),
                'type' => 'date/all-options',
                'sectionId' => 'date',
                'default' => 'thisyear',
            ],
        ];
    }
}
