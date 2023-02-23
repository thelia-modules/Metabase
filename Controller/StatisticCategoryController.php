<?php

    namespace Metabase\Controller;

    use Metabase\Metabase;
    use Metabase\Service\MetabaseService;
    use Thelia\Controller\Admin\AdminController;
    use Thelia\Core\Translation\Translator;

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
        public function generateCategoryStatisticsMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, array $fields, bool $count = false)
        {
            $translator = Translator::getInstance();
            $dashboardName = $translator->trans("Dashboard Sales Category", [], Metabase::DOMAIN_NAME);
            $descriptionDashboard = $translator->trans("Sales Statistic by Category", [], Metabase::DOMAIN_NAME);
            $cardName = $translator->trans("CategorySalesCard_", [], Metabase::DOMAIN_NAME);
            $descriptionCard = $translator->trans("card of Sales Statistic by Category", [], Metabase::DOMAIN_NAME);

            $query = "select SUM(ROUND(order_product.quantity * IF(order_product.was_in_promo = 1, order_product.promo_price, order_product.price), 2) ) AS TOTAL, DATE_FORMAT(`order_product`.`created_at`, \"%m\") as Date 
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where 1=0 [[or {{category}}]] and {{date}} [[and {{orderType}}]]
                group by Date";

            if ($count){
                $dashboardName = $translator->trans("Dashboard Count Category", [], Metabase::DOMAIN_NAME);
                $descriptionDashboard = $translator->trans("Count Statistic by Category", [], Metabase::DOMAIN_NAME);
                $cardName = $translator->trans("CategoryCard_", [], Metabase::DOMAIN_NAME);
                $descriptionCard = $translator->trans("card of Count Statistic by Category", [], Metabase::DOMAIN_NAME);

                $query = "select SUM(`order_product`.quantity) as TOTAL, DATE_FORMAT(`order_product`.`created_at`, \"%m\") as Date 
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where 1=0 [[or {{category}}]] and {{date}} [[and {{orderType}}]]
                group by Date";
            }

            $dashboard = json_decode($metabaseService->createDashboard($dashboardName, $descriptionDashboard, $collectionId));

            $fieldCategoryRef = $metabaseService->searchField($fields, "Meta Title", "Category I18n");
            $fieldDate = $metabaseService->searchField($fields, "Created At", "Order");
            $fieldOrderType = $metabaseService->searchField($fields, "Status ID", "Order");

            $defaultOrderType = $metabaseService->getDefaultOrderType();

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
                ["date_1" => "enabled", "date_2" => "enabled", "category_reference" => "enabled", "order_type" => "enabled"], $parameters);
        }
    }