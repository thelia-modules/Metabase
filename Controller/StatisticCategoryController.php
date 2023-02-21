<?php

    namespace Metabase\Controller;

    use Metabase\Service\MetabaseService;
    use Thelia\Controller\Admin\AdminController;

    class StatisticCategoryController extends AdminController
    {
        /**
         * @param MetabaseService $metabaseService
         * @param int $databaseId
         * @param int $collectionId
         * @param bool $count
         * true == Create a dashboard and cards with categories numbers of sales for a selected date /
         * false == Create a dashboard and cards with categories sales for a selected date
         */
        public function generateCategoryStatisticsMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, bool $count = false)
        {
            $dashboardName = "Statistic Sales Category";
            $cardName = "CategorySalesCard_";

            $query = "select SUM(ROUND(order_product.quantity * IF(order_product.was_in_promo = 1, order_product.promo_price, order_product.price), 2) ) AS TOTAL, DATE_FORMAT(`order_product`.`created_at`, \"%m\") as Date 
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where [[{{category}} and]] {{date}}
                group by Date";

            if ($count){
                $dashboardName = "Statistic Category";
                $cardName = "CategoryCard_";

                $query = "select SUM(`order_product`.quantity) as TOTAL, DATE_FORMAT(`order_product`.`created_at`, \"%m\") as Date 
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where {{category}} and {{date}}
                group by Date";
            }

            $dashboard = json_decode($metabaseService->createDashboard($dashboardName, "Sales Statistic by Category", $collectionId));

            $fields = json_decode($metabaseService->getAllField($databaseId));

            $fieldCategoryRef = $metabaseService->searchField($fields, "Meta Title", "Category I18n");

            $fieldDate = $metabaseService->searchField($fields, "Created At", "Order");

            $card = json_decode($metabaseService->createCard(
                ["graph.dimensions" => ["Date"],
                    "graph.series_order_dimension" => null,
                    "graph.series_order" => null,
                    "graph.metrics" => ["category"]
                ],
                [
                    [
                        "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                        "type" => "string/=",
                        "target" => [
                            "dimension",
                            [
                                "template-tag",
                                "category"
                            ]
                        ],
                        "name" => "Category",
                        "slug" => "category",
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
                "card of Statistic by Category",
                [
                    "database" => $databaseId,
                    "native" => [
                        "template-tags" =>  [
                            "category" => [
                                "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                                "name" => "category",
                                "display-name" => "Category",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldCategoryRef,
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
                    "graph.metrics" => ["category"]
                ],
                [
                    [
                        "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                        "type" => "string/=",
                        "target" => [
                            "dimension",
                            [
                                "template-tag",
                                "category"
                            ]
                        ],
                        "name" => "Category",
                        "slug" => "category",
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
                "card of Statistic by Category",
                [
                    "database" => $databaseId,
                    "native" => [
                        "template-tags" =>  [
                            "category" => [
                                "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                                "name" => "category",
                                "display-name" => "Category",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldCategoryRef,
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
                            "category"
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
                            "category"
                        ]
                    ]
                ]
            ];

            $series = json_decode($metabaseService->getCard($card2->id), true);
            $metabaseService->resizeCards($dashboard->id, $dashboardCard->id, $parameter_mappings, [$series]);

            $parameters = [
                [
                    "name" => "Category Reference",
                    "slug" => "category_reference",
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
                ["date_1" => "enabled", "date_2" => "enabled", "category_reference" => "enabled"], $parameters);
        }
    }