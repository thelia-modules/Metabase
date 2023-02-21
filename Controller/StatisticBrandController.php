<?php

namespace Metabase\Controller;

use Metabase\Service\MetabaseService;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Statistic\Statistic;
use Thelia\Controller\Admin\AdminController;
use Thelia\Model\Map\OrderProductTableMap;
use Thelia\Model\Map\ProductTableMap;
use Thelia\Model\OrderQuery;

class StatisticBrandController extends AdminController
{
    /**
     * @param MetabaseService $metabaseService
     * @param int $databaseId
     * @param int $collectionId
     * @param bool $count
     * true == Create a dashboard and cards with brands numbers of sales for a selected date /
     * false == Create a dashboard and cards with brands sales for a selected date
     */
    public function generateBrandStatisticsMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, bool $count = false)
    {
        $dashboardName = "Statistic Sales Brand";
        $cardName = "BrandSalesCard_";

        $query = "SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS TOTAL, 
            DATE_FORMAT(`order_product`.`created_at`,\"%m\") as Date 
            FROM `order` 
            INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
            INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
            INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
            WHERE {{brand}} and {{date}}
            GROUP BY date";


        if ($count){
            $dashboardName = "Statistic Brand";
            $cardName = "BrandCard_";

            $query = "SELECT SUM(order_product.quantity) AS TOTAL, 
                DATE_FORMAT(`order_product`.`created_at`,\"%m\") as Date 
                FROM `order` 
                INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
                INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
                WHERE {{brand}} and {{date}}
                GROUP BY date";
        }

        $dashboard = json_decode($metabaseService->createDashboard($dashboardName, "Sales Statistic by Brand", $collectionId));

        $fields = json_decode($metabaseService->getAllField($databaseId));

        $fieldBrandRef = $metabaseService->searchField($fields, "Meta Title", "Brand I18n");

        $fieldDate = $metabaseService->searchField($fields, "Created At", "Order");

        $card = json_decode($metabaseService->createCard(
            ["graph.dimensions" => ["Date"],
                "graph.series_order_dimension" => null,
                "graph.series_order" => null,
                "graph.metrics" => ["TOTAL"]
            ],
            [
                [
                    "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                    "type" => "string/=",
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "brand"
                        ]
                    ],
                    "name" => "Brand",
                    "slug" => "brand",
                    "default" => null
                ],
                [
                    "id" => "42bbcb76-e12d-d9ec-19bd-22a497454a1e",
                    "type" => "date/all-options",
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "date"
                        ]
                    ],
                    "name" => "Date",
                    "slug" => "date",
                    "default" => "past1years"
                ]
            ],
            $cardName."1",
            "card of Statistic by Brand",
            [
                "database" => $databaseId,
                "native" => [
                    "template-tags" =>  [
                        "brand" => [
                            "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                            "name" => "brand",
                            "display-name" => "Brand",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldBrandRef,
                                null
                            ],
                            "widget-type" => "string/=",
                            "default" => null
                        ],
                        "date" => [
                            "id" => "42bbcb76-e12d-d9ec-19bd-22a497454a1e",
                            "name" => "date",
                            "display-name" => "Date",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldDate,
                                null
                            ],
                            "widget-type" => "date/all-options",
                            "required" => true,
                            "default" => "past1years"
                        ]
                    ],
                    "query" => $query
                ],
                "type" => "native"
            ],
            "line",
            $collectionId
        ));

        $card2 = json_decode($metabaseService->createCard(
            ["graph.dimensions" => ["Date"],
                "graph.series_order_dimension" => null,
                "graph.series_order" => null,
                "graph.metrics" => ["brand"]
            ],
            [
                [
                    "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                    "type" => "string/=",
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "brand"
                        ]
                    ],
                    "name" => "Brand",
                    "slug" => "brand",
                    "default" => null
                ],
                [
                    "id" => "42bbcb76-e12d-d9ec-19bd-22a497454a1e",
                    "type" => "date/all-options",
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "date"
                        ]
                    ],
                    "name" => "Date",
                    "slug" => "date",
                    "default" => "thisyear"
                ]
            ],
            $cardName."2",
            "card of Statistic by Brand",
            [
                "database" => $databaseId,
                "native" => [
                    "template-tags" =>  [
                        "brand" => [
                            "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                            "name" => "brand",
                            "display-name" => "Brand",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldBrandRef,
                                null
                            ],
                            "widget-type" => "string/=",
                            "default" => null
                        ],
                        "date" => [
                            "id" => "42bbcb76-e12d-d9ec-19bd-22a497454a1e",
                            "name" => "date",
                            "display-name" => "Date",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldDate,
                                null
                            ],
                            "widget-type" => "date/all-options",
                            "required" => true,
                            "default" => "thisyear"
                        ]
                    ],
                    "query" => $query
                ],
                "type" => "native"
            ],
            "line",
            $collectionId
        ));

        $dashboardCard = json_decode($metabaseService->addCardToDashboard($dashboard->id, $card->id));

        $parameter_mappings = [
            [
                "parameter_id" => "eb41a963",
                "card_id" => $card2->id,
                "target" => [
                    "dimension",
                    [
                        "template-tag",
                        "date"
                    ]
                ]
            ],
            [
                "parameter_id" => "44928ac5",
                "card_id" => $card->id,
                "target" => [
                    "dimension",
                    [
                        "template-tag",
                        "date"
                    ]
                ]
            ],
            [
                "parameter_id" => "23d0dc83",
                "card_id" => $card2->id,
                "target" => [
                    "dimension",
                    [
                        "template-tag",
                        "brand"
                    ]
                ]
            ],
            [
                "parameter_id" => "23d0dc83",
                "card_id" => $card->id,
                "target" => [
                    "dimension",
                    [
                        "template-tag",
                        "brand"
                    ]
                ]
            ]
        ];

        $series = json_decode($metabaseService->getCard($card2->id), true);
        $metabaseService->resizeCards($dashboard->id, $dashboardCard->id, $parameter_mappings, [$series]);

        $parameters = [
            [
                "name" => "Brand Reference",
                "slug" => "brand_reference",
                "id" => "23d0dc83",
                "type" => "string/=",
                "sectionId" => "string"
            ],
            [
                "name" => "Date 1",
                "slug" => "date_1",
                "id" => "44928ac5",
                "type" => "date/all-options",
                "sectionId" => "date",
                "default" => "thisyear"
            ],
            [
                "name" => "Date 2",
                "slug" => "date_2",
                "id" => "eb41a963",
                "type" => "date/all-options",
                "sectionId" => "date",
                "default" => "past1years"
            ]];

        $metabaseService->publishDashboard($dashboard->id,
            ["date_1" => "enabled", "date_2" => "enabled", "brand_reference" => "enabled"], $parameters);
    }
}