<?php

    namespace Metabase\Controller;

    use DateTime;
    use Metabase\Metabase;
    use Metabase\Service\MetabaseService;
    use Thelia\Controller\Admin\AdminController;
    use Thelia\Core\Translation\Translator;

    class MainStatisticMetabaseController extends AdminController
    {
        /**
         * Creer un tableau avec le chiffre d'affaires du magasin
         */
        public function generateMainStatisticMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, array $fields)
        {
            $translator = Translator::getInstance();
            $dashboardName = $translator->trans("MainDashboard", [], Metabase::DOMAIN_NAME);
            $descriptionDashboard = $translator->trans("main dashboard", [], Metabase::DOMAIN_NAME);
            $cardName = $translator->trans("MainCard", [], Metabase::DOMAIN_NAME);
            $descriptionCard = $translator->trans("main card", [], Metabase::DOMAIN_NAME);
            $cardNameNumber = $translator->trans("MainCardNumber", [], Metabase::DOMAIN_NAME);
            $descriptionCardNumber = $translator->trans("main card with number", [], Metabase::DOMAIN_NAME);

            $dashboard = json_decode($metabaseService->createDashboard($dashboardName, $descriptionDashboard, $collectionId));

            $fieldOrderType = $metabaseService->searchField($fields, "Status ID", "Order");

            $defaultOrderType = $metabaseService->getDefaultOrderType();

            $endDate = new DateTime('now');
            $startDate = new DateTime('now');
            $startDate = $startDate->modify('-31 day');

            $uuidStartDate = uniqid();
            $uuidEndDate = uniqid();
            $uuidOrderType = uniqid();
            $uuidParamStart = uniqid();
            $uuidParamEnd = uniqid();
            $uuidParamOrderType = uniqid();

            $query = "SELECT `order`.`invoice_date` as DATE, 
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) + SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product_tax`.PROMO_AMOUNT,`order_product_tax`.AMOUNT))) - SUM(`order`.discount) AS TOTAL 
                    FROM `order` 
                    INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                    LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`) 
                    WHERE (`order`.`invoice_date`>={{start}} AND `order`.`invoice_date`<={{end}}) AND {{orderType}} 
                    GROUP BY DATE
                    order BY DATE";

            $query2 = "SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) + SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product_tax`.PROMO_AMOUNT,`order_product_tax`.AMOUNT))) - SUM(`order`.discount) AS TOTAL 
                    FROM `order`
                    INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                    LEFT JOIN `order_product_tax` ON (`order_product`.`id`=`order_product_tax`.`order_product_id`) 
                    WHERE (`order`.`invoice_date`>={{start}} AND `order`.`invoice_date`<={{end}}) AND {{orderType}}";

            $card = json_decode($metabaseService->createCard(
                [
                    "graph.dimensions" => ["DATE"],
                    "graph.metrics" => ["TOTAL"],
                    "column_settings" => [
                        "[\"name\",\"TOTAL\"]" => [
                            "suffix" => "€",
                            "number_separators" => ", "
                        ]
                    ],
                    "series_settings" => [
                        "TOTAL" => [
                            "line.missing" => "zero"
                        ]
                    ]
                ],
                [
                    [
                        "id" => $uuidStartDate,
                        "type" => "date/single",
                        "target" => ["dimension", ["template-tag", "start"]],
                        "name" => "Start",
                        "slug" => "start",
                        "default" => $startDate->format("Y-m-d")
                    ],
                    [
                        "id" => $uuidEndDate,
                        "type" => "date/single",
                        "target" => ["dimension", ["template-tag", "end"]],
                        "name" => "End",
                        "slug" => "end",
                        "default" => $endDate->format("Y-m-d")
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
                $cardName,
                $descriptionCard,
                [
                    "database" => $databaseId,
                    "native" => [
                        "template-tags" => [
                            "start" => [
                                "id" => $uuidStartDate,
                                "name" => "start",
                                "display-name" => "Start",
                                "type" => "date",
                                "default" => $startDate->format("Y-m-d"),
                                "required" => true
                            ],
                            "end" => [
                                "id" => $uuidEndDate,
                                "name" => "end",
                                "display-name" => "End",
                                "type" => "date",
                                "default" => $endDate->format("Y-m-d"),
                                "required" => true
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

            $card3 = json_decode($metabaseService->createCard(
                [
                    "graph.dimensions" => ["DATE"],
                    "graph.metrics" => ["TOTAL"],
                    "column_settings" => [
                        "[\"name\",\"TOTAL\"]" => [
                            "suffix" => "€",
                            "number_separators" => ", "
                        ]
                    ],
                    "series_settings" => [
                        "TOTAL" => [
                            "line.missing" => "zero"
                        ]
                    ]
                ],
                [
                    [
                        "id" => $uuidStartDate,
                        "type" => "date/single",
                        "target" => ["dimension", ["template-tag", "start"]],
                        "name" => "Start",
                        "slug" => "start",
                        "default" => $startDate->format("Y-m-d")
                    ],
                    [
                        "id" => $uuidEndDate,
                        "type" => "date/single",
                        "target" => ["dimension", ["template-tag", "end"]],
                        "name" => "End",
                        "slug" => "end",
                        "default" => $endDate->format("Y-m-d")
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
                $cardNameNumber,
                $descriptionCardNumber,
                [
                    "database" => $databaseId,
                    "native" => [
                        "template-tags" => [
                            "start" => [
                                "id" => $uuidStartDate,
                                "name" => "start",
                                "display-name" => "Start",
                                "type" => "date",
                                "default" => $startDate->format("Y-m-d"),
                                "required" => true
                            ],
                            "end" => [
                                "id" => $uuidEndDate,
                                "name" => "end",
                                "display-name" => "End",
                                "type" => "date",
                                "default" => $endDate->format("Y-m-d"),
                                "required" => true
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
                        "query" => $query2
                    ],
                    "type" => "native"
                ],
                "scalar",
                $collectionId
            ));

            $dashboardCard = json_decode($metabaseService->addCardToDashboard($dashboard->id, $card->id));
            $dashboardCard2 = json_decode($metabaseService->addCardToDashboard($dashboard->id, $card3->id));

            $parameter_mappings = [
                [
                    "parameter_id" => $uuidParamStart,
                    "card_id" => $card->id,
                    "target" => [
                        "variable",
                        [
                            "template-tag",
                            "start"
                        ]
                    ]
                ],
                [
                    "parameter_id" => $uuidParamStart,
                    "card_id" => $card3->id,
                    "target" => [
                        "variable",
                        [
                            "template-tag",
                            "start"
                        ]
                    ]
                ],
                [
                    "parameter_id" => $uuidParamEnd,
                    "card_id" => $card->id,
                    "target" => [
                        "variable",
                        [
                            "template-tag",
                            "end"
                        ]
                    ]
                ],
                [
                    "parameter_id" => $uuidParamEnd,
                    "card_id" => $card3->id,
                    "target" => [
                        "variable",
                        [
                            "template-tag",
                            "end"
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
                    "card_id" => $card3->id,
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "orderType"
                        ]
                    ]
                ],
            ];

            $metabaseService->resizeCards($dashboard->id, $dashboardCard->id, $parameter_mappings, [], 0,0, 18, 4);
            $metabaseService->resizeCards($dashboard->id, $dashboardCard2->id, $parameter_mappings, [], 6,0, 18, 3);

            $parameters = [
                [
                    "name" => "Date Start",
                    "slug" => "start",
                    "id" => $uuidParamStart,
                    "type" => "date/single",
                    "sectionId" => "date",
                    "default" => $startDate->format("Y-m-d")
                ],
                [
                    "name" => "Date End",
                    "slug" => "end",
                    "id" => $uuidParamEnd,
                    "type" => "date/single",
                    "sectionId" => "date",
                    "default" => $endDate->format("Y-m-d")
                ],
                [
                    "name" => "orderType",
                    "slug" => "orderType",
                    "id" => $uuidParamOrderType,
                    "type" => "string/=",
                    "sectionId" => "string",
                    "default" => $defaultOrderType
                ]];

            $metabaseService->publishDashboard($dashboard->id, ["start" => "enabled", "end" => "enabled", "orderType" => "enabled"], $parameters);
        }
    }