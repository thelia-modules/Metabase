<?php

namespace Metabase\Controller;

use Metabase\Metabase;
use Metabase\Service\MetabaseService;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Statistic\Statistic;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\Translation\Translator;
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
    public function generateBrandStatisticsMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, array $fields, bool $count = false)
    {
        $translator = Translator::getInstance();
        $dashboardName = $translator->trans("Dashboard Sales Brand", [], Metabase::DOMAIN_NAME);
        $descriptionDashboard = $translator->trans("Sales Statistic by Brand", [], Metabase::DOMAIN_NAME);
        $cardName = $translator->trans("BrandSalesCard_", [], Metabase::DOMAIN_NAME);
        $descriptionCard = $translator->trans("card of Sales Statistic by Brand", [], Metabase::DOMAIN_NAME);
        $column_settings = [
            "[\"name\",\"TOTAL\"]" => [
                "suffix" => "€",
                "number_separators" => ", "
            ]
        ];

        $query = "SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS TOTAL, 
            DATE_FORMAT(`order_product`.`created_at`,\"%b\") as DATE 
            FROM `order` 
            INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
            INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
            INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
            WHERE 1=0 [[or {{brand}}]] and {{date}} [[and {{orderType}}]]
            GROUP BY date";


        if ($count){
            $dashboardName = $translator->trans("Dashboard Count Brand", [], Metabase::DOMAIN_NAME);
            $descriptionDashboard = $translator->trans("Count Statistic by Brand", [], Metabase::DOMAIN_NAME);
            $cardName = $translator->trans("BrandCard_", [], Metabase::DOMAIN_NAME);
            $descriptionCard = $translator->trans("card of Count Statistic by Brand", [], Metabase::DOMAIN_NAME);
            $column_settings = [];

            $query = "SELECT SUM(order_product.quantity) AS TOTAL, 
                DATE_FORMAT(`order_product`.`created_at`,\"%b\") as DATE 
                FROM `order` 
                INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
                INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
                WHERE 1=0 [[or {{brand}}]] and {{date}} [[and {{orderType}}]]
                GROUP BY DATE";
        }

        $dashboard = json_decode($metabaseService->createDashboard($dashboardName, $descriptionDashboard, $collectionId));

        $fieldBrandRef = $metabaseService->searchField($fields, "Meta Title", "Brand I18n");
        $fieldDate = $metabaseService->searchField($fields, "Created At", "Order");
        $fieldOrderType = $metabaseService->searchField($fields, "Status ID", "Order");

        $defaultOrderType = $metabaseService->getDefaultOrderType();

        $card = json_decode($metabaseService->createCard(
            ["graph.dimensions" => ["DATE"],
                "graph.series_order_dimension" => null,
                "graph.series_order" => null,
                "graph.metrics" => ["TOTAL"],
                "column_settings" => $column_settings
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
                    "name" => "DATE",
                    "slug" => "date",
                    "default" => "past1years"
                ],
                [
                    "id" => "f7050c92-f9e0-9453-81fb-58062a1446d6",
                    "type" => "string/=",
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "order_type"
                        ]
                    ],
                    "name" => "Ordertype",
                    "slug" => "order_type",
                    "default" => $defaultOrderType
                ]
            ],
            $cardName."1",
            $descriptionCard,
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
                            "display-name" => "DATE",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldDate,
                                null
                            ],
                            "widget-type" => "date/all-options",
                            "required" => true,
                            "default" => "past1years"
                        ],
                        "order_type" => [
                            "id" => "f7050c92-f9e0-9453-81fb-58062a1446d6",
                            "name" => "order_type",
                            "display-name" => "Ordertype",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldOrderType,
                                null
                            ],
                            "widget-type" => "string/=",
                            "default" => $defaultOrderType
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
            ["graph.dimensions" => ["DATE"],
                "graph.series_order_dimension" => null,
                "graph.series_order" => null,
                "graph.metrics" => ["brand"],
                "column_settings" => $column_settings
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
                    "name" => "DATE",
                    "slug" => "date",
                    "default" => "thisyear"
                ],
                [
                    "id" => "f7050c92-f9e0-9453-81fb-58062a1446d6",
                    "type" => "string/=",
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "order_type"
                        ]
                    ],
                    "name" => "Ordertype",
                    "slug" => "order_type",
                    "default" => $defaultOrderType
                ]
            ],
            $cardName."2",
            $descriptionCard,
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
                            "display-name" => "DATE",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldDate,
                                null
                            ],
                            "widget-type" => "date/all-options",
                            "required" => true,
                            "default" => "thisyear"
                        ],
                        "order_type" => [
                            "id" => "f7050c92-f9e0-9453-81fb-58062a1446d6",
                            "name" => "order_type",
                            "display-name" => "Ordertype",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldOrderType,
                                null
                            ],
                            "widget-type" => "string/=",
                            "default" => $defaultOrderType
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
            ],
            [
                "parameter_id" => "64b9491",
                "card_id" => $card->id,
                "target" => [
                    "dimension",
                    [
                        "template-tag",
                        "order_type"
                    ]
                ]
            ],
            [
                "parameter_id" => "64b9491",
                "card_id" => $card2->id,
                "target" => [
                    "dimension",
                    [
                        "template-tag",
                        "order_type"
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
            ],
            [
                "name" => "orderType",
                "slug" => "order_type",
                "id" => "64b9491",
                "type" => "string/=",
                "sectionId" => "string",
                "default" => $defaultOrderType
            ]];

        $metabaseService->publishDashboard($dashboard->id,
            ["date_1" => "enabled", "date_2" => "enabled", "brand_reference" => "enabled", "order_type" => "enabled"], $parameters);
    }
}