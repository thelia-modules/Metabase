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
            $column_settings = [
                "[\"name\",\"TOTAL\"]" => [
                    "suffix" => "€",
                    "number_separators" => ", "
                ]
            ];

            $uuidCategory = uniqid();
            $uuidDate = uniqid();
            $uuidOrderType = uniqid();
            $uuidParamCategory = uniqid();
            $uuidParamDate1 = uniqid();
            $uuidParamDate2 = uniqid();
            $uuidParamOrderType = uniqid();

            $query = "select SUM(ROUND(order_product.quantity * IF(order_product.was_in_promo = 1, order_product.promo_price, order_product.price), 2) ) AS TOTAL,
                DATE_FORMAT(`order`.`invoice_date`, \"%b\") as DATE
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where 1=0 [[or {{category}}]] and {{date}} [[and {{orderType}}]]
                group by DATE_FORMAT(`order`.`invoice_date`, \"%m\"), DATE
                order by DATE_FORMAT(`order`.`invoice_date`, \"%m\")";

            if ($count){
                $dashboardName = $translator->trans("Dashboard Count Category", [], Metabase::DOMAIN_NAME);
                $descriptionDashboard = $translator->trans("Count Statistic by Category", [], Metabase::DOMAIN_NAME);
                $cardName = $translator->trans("CategoryCard_", [], Metabase::DOMAIN_NAME);
                $descriptionCard = $translator->trans("card of Count Statistic by Category", [], Metabase::DOMAIN_NAME);
                $column_settings = [];

                $query = "select SUM(`order_product`.quantity) as TOTAL,
                DATE_FORMAT(`order`.`invoice_date`, \"%b\") as DATE
                from `order`
                join `order_product` on `order`.id = `order_product`.order_id
                join `product` on `product`.ref = `order_product`.product_ref
                join `product_category` on (`product`.`id`=`product_category`.`product_id`)
                join `category_i18n` on `category_i18n`.`id` = `product_category`.`category_id`
                where 1=0 [[or {{category}}]] and {{date}} [[and {{orderType}}]]
                group by DATE_FORMAT(`order`.`invoice_date`, \"%m\"), DATE
                order by DATE_FORMAT(`order`.`invoice_date`, \"%m\")";
            }

            $dashboard = json_decode($metabaseService->createDashboard($dashboardName, $descriptionDashboard, $collectionId));

            $fieldCategoryRef = $metabaseService->searchField($fields, "Title", "Category I18n");
            $fieldDate = $metabaseService->searchField($fields, "Invoice Date", "Order");
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
                        "id" => $uuidCategory,
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
                        "id" => $uuidDate,
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
                        "id" => $uuidOrderType,
                        "type" => "string/=",
                        "target" => [
                            "dimension",
                            [
                                "template-tag",
                                "orderType"
                            ]
                        ],
                        "name" => "Ordertype",
                        "slug" => "orderType",
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
                                "id" => $uuidCategory,
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
                                "id" => $uuidDate,
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
                            "orderType" => [
                                "id" => $uuidOrderType,
                                "name" => "orderType",
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
                    "graph.metrics" => ["TOTAL"],
                    "column_settings" => $column_settings
                ],
                [
                    [
                        "id" => $uuidCategory,
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
                        "id" => $uuidDate,
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
                    "id" => $uuidOrderType,
                    "type" => "string/=",
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "orderType"
                        ]
                    ],
                    "name" => "Ordertype",
                    "slug" => "orderType",
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
                                "id" => $uuidCategory,
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
                                "id" => $uuidDate,
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
                            "orderType" => [
                                "id" => $uuidOrderType,
                                "name" => "orderType",
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
                    "parameter_id" => $uuidParamDate1,
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
                    "parameter_id" => $uuidParamDate2,
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
                    "parameter_id" => $uuidParamCategory,
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
                    "parameter_id" => $uuidParamCategory,
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
                    "parameter_id" => $uuidParamOrderType,
                    "card_id" => $card->id,
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "orderType"
                        ]
                    ]
                ],
                [
                    "parameter_id" => $uuidParamOrderType,
                    "card_id" => $card2->id,
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "orderType"
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
                    "id" => $uuidParamCategory,
                    "type" => "string/=",
                    "sectionId" => "string"
                ],
                [
                    "name" => "Date 1",
                    "slug" => "date_1",
                    "id" => $uuidParamDate1,
                    "type" => "date/all-options",
                    "sectionId" => "date",
                    "default" => "thisyear"
                ],
                [
                    "name" => "Date 2",
                    "slug" => "date_2",
                    "id" => $uuidParamDate2,
                    "type" => "date/all-options",
                    "sectionId" => "date",
                    "default" => "past1years"
                ],
                [
                    "name" => "orderType",
                    "slug" => "orderType",
                    "id" => $uuidParamOrderType,
                    "type" => "string/=",
                    "sectionId" => "string",
                    "default" => $defaultOrderType
                ]];

            $metabaseService->publishDashboard($dashboard->id,
                ["date_1" => "enabled", "date_2" => "enabled", "category_reference" => "enabled", "orderType" => "enabled"], $parameters);
        }
    }