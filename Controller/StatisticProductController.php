<?php

    namespace Metabase\Controller;

    use Metabase\Service\MetabaseService;
    use Thelia\Controller\Admin\AdminController;

    class StatisticProductController extends AdminController
    {
        /**
         * @param MetabaseService $metabaseService
         * @param int $databaseId
         * @param int $collectionId
         * @param bool $count
         * true == Create a dashboard and cards with products numbers of sales for a selected date /
         * false == Create a dashboard and cards with products sales for a selected date
         */
        public function generateProductStatisticsMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, $count = false)
        {
            $dashboardName = "Statistic Sales Product";
            $cardName = "ProductsSalesCard_";

            $query = "select SUM((`order_product`.quantity * IF(`order_product`.was_in_promo,`order_product`.promo_price,`order_product`.price))) AS TOTAL, 
                DATE_FORMAT(`order_product`.`created_at`,  \"%m\") as Date 
                from `order`
                join `order_product` on `order`.`id` = `order_product`.`order_id`
                join `order_product_tax` on `order_product`.`id` = `order_product_tax`.`order_product_id`
                join `product` on `product`.ref = `order_product`.product_ref
                where {{product}} and {{date}}
                group by Date";

            if ($count){
                $dashboardName = "Statistic Product";
                $cardName = "ProductsCard_";

                $query = "select SUM(`order_product`.quantity) as TOTAL, 
                DATE_FORMAT(`order_product`.`created_at`, \"%m\") as Date 
                from `order`
                join `order_status` on `order`.status_id = `order_status`.id
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                [[where {{product}}]] and {{date}}
                group by Date";
            }

            $dashboard = json_decode($metabaseService->createDashboard($dashboardName, "Sales Statistic by Product", $collectionId));

            $jsonFields = $metabaseService->getAllField($databaseId);

            $fieldProdRef = $metabaseService->searchField(json_decode($jsonFields), "Ref", "Product");
            $fieldDate = $metabaseService->searchField(json_decode($jsonFields), "Created At", "Order");

            $jsonCard = $metabaseService->createCard(
                ["graph.dimensions" => ["Date"],
                    "graph.series_order_dimension" => null,
                    "graph.series_order" => null,
                    "graph.metrics" => ["product"]
                ],
                [
                    [
                        "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                        "type" => "string/=",
                        "target" => [
                            "dimension",
                            [
                                "template-tag",
                                "product"
                            ]
                        ],
                        "name" => "product",
                        "slug" => "product",
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
                "card of Statistic by Product",
                [
                    "database" => $databaseId,
                    "native" => [
                        "template-tags" =>  [
                            "product" => [
                                "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                                "name" => "product",
                                "display-name" => "Product",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldProdRef,
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
            );

            $jsonCard2 = $metabaseService->createCard(
                ["graph.dimensions" => ["Date"],
                    "graph.series_order_dimension" => null,
                    "graph.series_order" => null,
                    "graph.metrics" => ["product"]
                ],
                [
                    [
                        "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                        "type" => "string/=",
                        "target" => [
                            "dimension",
                            [
                                "template-tag",
                                "product"
                            ]
                        ],
                        "name" => "product",
                        "slug" => "product",
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
                "card of Statistic by Product",
                [
                    "database" => $databaseId,
                    "native" => [
                        "template-tags" =>  [
                            "product" => [
                                "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                                "name" => "product",
                                "display-name" => "Product",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldProdRef,
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
            );

            $card = json_decode($jsonCard);
            $card2 = json_decode($jsonCard2);

            $jsonAddCard = $metabaseService->addCardToDashboard($dashboard->id, $card->id);
            $dashboardCard = json_decode($jsonAddCard);

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
                            "product"
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
                            "product"
                        ]
                    ]
                ]
            ];

            $series = json_decode($metabaseService->getCard($card2->id), true);
            $metabaseService->resizeCards($dashboard->id, $dashboardCard->id, $parameter_mappings, [$series]);

            $parameters = [
                [
                    "name" => "Product Reference",
                    "slug" => "product_reference",
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
                ["date_1" => "enabled", "date_2" => "enabled", "product_reference" => "enabled"], $parameters);
        }
    }