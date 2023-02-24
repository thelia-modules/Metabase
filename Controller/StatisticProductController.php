<?php

    namespace Metabase\Controller;

    use Metabase\Metabase;
    use Metabase\Service\MetabaseService;
    use Thelia\Controller\Admin\AdminController;
    use Thelia\Core\Translation\Translator;

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
        public function generateProductStatisticsMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, array $fields, $count = false)
        {
            $translator = Translator::getInstance();
            $dashboardName = $translator->trans("Dashboard Sales Product", [], Metabase::DOMAIN_NAME);
            $descriptionDashboard = $translator->trans("Sales Statistic by Product", [], Metabase::DOMAIN_NAME);
            $cardName = $translator->trans("ProductsSalesCard_", [], Metabase::DOMAIN_NAME);
            $descriptionCard = $translator->trans("card of Sales Statistic by Product", [], Metabase::DOMAIN_NAME);
            $column_settings = [
                "[\"name\",\"TOTAL\"]" => [
                    "suffix" => "€",
                    "number_separators" => ", "
                ]
            ];

            $query = "select SUM((`order_product`.quantity * IF(`order_product`.was_in_promo,`order_product`.promo_price,`order_product`.price))) AS TOTAL, 
                DATE_FORMAT(`order_product`.`created_at`,  \"%b\") as DATE 
                from `order`
                join `order_product` on `order`.`id` = `order_product`.`order_id`
                join `order_product_tax` on `order_product`.`id` = `order_product_tax`.`order_product_id`
                join `product` on `product`.ref = `order_product`.product_ref
                where 1=0 [[or {{ref}}]][[or {{title}}]] and {{date}} [[and {{orderType}}]]
                group by DATE";

            if ($count){
                $dashboardName = $translator->trans("Dashboard Count Product", [], Metabase::DOMAIN_NAME);
                $descriptionDashboard = $translator->trans("Count Statistic by Product", [], Metabase::DOMAIN_NAME);
                $cardName = $translator->trans("ProductsCard_", [], Metabase::DOMAIN_NAME);
                $descriptionCard = $translator->trans("card of Count Statistic by Product", [], Metabase::DOMAIN_NAME);
                $column_settings = [];

                $query = "select SUM(`order_product`.quantity) as TOTAL, 
                DATE_FORMAT(`order_product`.`created_at`, \"%b\") as DATE 
                from `order`
                join `order_status` on `order`.status_id = `order_status`.id
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                where 1=0 [[or {{ref}}]][[or {{title}}]] and {{date}} [[and {{orderType}}]]
                group by DATE";
            }

            $dashboard = json_decode($metabaseService->createDashboard($dashboardName, $descriptionDashboard, $collectionId));

            $fieldProdRef = $metabaseService->searchField($fields, "Ref", "Product");
            $fieldProdTitle = $metabaseService->searchField($fields, "Title", "Order Product");
            $fieldDate = $metabaseService->searchField($fields, "Created At", "Order");
            $fieldOrderType = $metabaseService->searchField($fields, "Status ID", "Order");

            $defaultOrderType = $metabaseService->getDefaultOrderType();

            $card = json_decode($metabaseService->createCard(
                ["graph.dimensions" => [""],
                    "graph.series_order_dimension" => null,
                    "graph.series_order" => null,
                    "graph.metrics" => [null],
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
                                "ref"
                            ]
                        ],
                        "name" => "ref",
                        "slug" => "ref",
                        "default" => null
                    ],
                    [
                        "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                        "type" => "string/=",
                        "target" => [
                            "dimension",
                            [
                                "template-tag",
                                "title"
                            ]
                        ],
                        "name" => "title",
                        "slug" => "title",
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
                            "ref" => [
                                "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                                "name" => "ref",
                                "display-name" => "ref",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldProdRef,
                                    null
                                ],
                                "widget-type" => "string/=",
                                "default" => null
                            ],
                            "title" => [
                                "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                                "name" => "title",
                                "display-name" => "Title",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldProdTitle,
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
                    "graph.metrics" => ["ref"],
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
                                "ref"
                            ]
                        ],
                        "name" => "ref",
                        "slug" => "ref",
                        "default" => null
                    ],
                    [
                        "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                        "type" => "string/=",
                        "target" => [
                            "dimension",
                            [
                                "template-tag",
                                "title"
                            ]
                        ],
                        "name" => "title",
                        "slug" => "title",
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
                            "ref" => [
                                "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                                "name" => "ref",
                                "display-name" => "ref",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldProdRef,
                                    null
                                ],
                                "widget-type" => "string/=",
                                "default" => null
                            ],
                            "title" => [
                                "id" => "96525f8f-b1d4-c21b-4207-4b41357d57cd",
                                "name" => "title",
                                "display-name" => "Title",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldProdTitle,
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
                            "ref"
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
                            "ref"
                        ]
                    ]
                ],
                [
                    "parameter_id" => "23d0dc84",
                    "card_id" => $card2->id,
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "title"
                        ]
                    ]
                ],
                [
                    "parameter_id" => "23d0dc84",
                    "card_id" => $card->id,
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "title"
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
                    "name" => "Product Reference",
                    "slug" => "product_reference",
                    "id" => "23d0dc83",
                    "type" => "string/=",
                    "sectionId" => "string"
                ],
                [
                    "name" => "Product Title",
                    "slug" => "product_title",
                    "id" => "23d0dc84",
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
                ["date_1" => "enabled", "date_2" => "enabled", "product_reference" => "enabled", "product_title" => "enabled", "order_type" => "enabled"], $parameters);
        }
    }