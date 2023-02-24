<?php

    namespace Metabase\Controller;

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

            $fieldDate = $metabaseService->searchField($fields, "Created At", "Order");
            $fieldOrderType = $metabaseService->searchField($fields, "Status ID", "Order");

            $defaultOrderType = $metabaseService->getDefaultOrderType();

            $query = "select SUM(ROUND(IF(op.`was_in_promo`, op.`promo_price` + opt.`promo_amount`, op.`price` + opt.`amount`), 2) * op.`quantity`) - SUM(ROUND(`order`.`discount`, 2)) + SUM(`order`.`postage`) as TOTAL, 
                    DATE_FORMAT(op.`created_at`, \"%d/%m\") as DATE 
                    from `order_product` as op 
                    join `order_product_tax` as opt on op.`id` = opt.`order_product_id` 
                    join `order` on `order`.`id` = op.`order_id`
                    where {{start}} [[and {{orderType}}]]
                    group by DATE";

            $query2 = "select SUM(ROUND(IF(op.`was_in_promo`, op.`promo_price` + opt.`promo_amount`, op.`price` + opt.`amount`), 2) * op.`quantity`) - SUM(ROUND(`order`.`discount`, 2)) + SUM(`order`.`postage`) as TOTAL
                    from `order_product` as op 
                    join `order_product_tax` as opt on op.`id` = opt.`order_product_id` 
                    join `order` on `order`.`id` = op.`order_id`
                    where {{start}} [[and {{orderType}}]]";

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
                ],
                [
                    [
                        "id" => "908503d9-269d-df89-d591-79a5d8810583",
                        "type" => "date/relative",
                        "target" => ["dimension", ["template-tag", "start"]],
                        "name" => "Start",
                        "slug" => "start",
                        "default" => "past30days"
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
                $cardName,
                $descriptionCard,
                [
                    "database" => $databaseId,
                    "native" => [
                        "template-tags" => [
                            "start" => [
                                "id" => "908503d9-269d-df89-d591-79a5d8810583",
                                "name" => "start",
                                "display-name" => "Start",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldDate,
                                    null
                                ],
                                "widget-type" => "date/relative",
                                "default" => "past30days",
                                "required" => true
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

            $card3 = json_decode($metabaseService->createCard(
                [
                    "graph.dimensions" => ["DATE"],
                    "graph.metrics" => ["TOTAL"],
                    "column_settings" => [
                        "[\"name\",\"TOTAL\"]" => [
                            "suffix" => "€",
                            "number_separators" => ", "
                        ]
                    ]
                ],
                [
                    [
                        "id" => "908503d9-269d-df89-d591-79a5d8810583",
                        "type" => "date/relative",
                        "target" => ["dimension", ["template-tag", "start"]],
                        "name" => "Start",
                        "slug" => "start",
                        "default" => "past30days"
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
                $cardNameNumber,
                $descriptionCardNumber,
                [
                    "database" => $databaseId,
                    "native" => [
                        "template-tags" => [
                            "start" => [
                                "id" => "908503d9-269d-df89-d591-79a5d8810583",
                                "name" => "start",
                                "display-name" => "Start",
                                "type" => "dimension",
                                "dimension" => [
                                    "field",
                                    $fieldDate,
                                    null
                                ],
                                "widget-type" => "date/relative",
                                "default" => "past30days",
                                "required" => true
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
                    "parameter_id" => "5ef8a7ee",
                    "card_id" => $card->id,
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "start"
                        ]
                    ]
                ],
                [
                    "parameter_id" => "5ef8a7ee",
                    "card_id" => $card3->id,
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "start"
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
                    "card_id" => $card3->id,
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "order_type"
                        ]
                    ]
                ],
            ];

            $metabaseService->resizeCards($dashboard->id, $dashboardCard->id, $parameter_mappings, [], 0,0, 18, 4);
            $metabaseService->resizeCards($dashboard->id, $dashboardCard2->id, $parameter_mappings, [], 6,0, 18, 3);

            $parameters = [
                [
                    "name" => "Date 1",
                    "slug" => "date_1",
                    "id" => "5ef8a7ee",
                    "type" => "date/all-options",
                    "sectionId" => "date",
                    "default" => "past30days"
                ],
                [
                    "name" => "orderType",
                    "slug" => "order_type",
                    "id" => "64b9491",
                    "type" => "string/=",
                    "sectionId" => "string",
                    "default" => $defaultOrderType
                ]];

            $metabaseService->publishDashboard($dashboard->id, ["date_1" => "enabled", "order_type" => "enabled"], $parameters);
        }
    }