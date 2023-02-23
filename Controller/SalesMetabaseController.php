<?php

    namespace Metabase\Controller;

    use Metabase\Metabase;
    use Metabase\Service\MetabaseService;
    use Thelia\Controller\Admin\AdminController;
    use Thelia\Core\Translation\Translator;

    class SalesMetabaseController extends AdminController
    {
        /**
         * Creer un tableau avec le chiffre d'affaires du magasin
         */
        public function generateSaleMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, array $fields)
        {
            $translator = Translator::getInstance();
            $dashboardName = $translator->trans("SalesDashboard", [], Metabase::DOMAIN_NAME);
            $descriptionDashboard = $translator->trans("sales of the store", [], Metabase::DOMAIN_NAME);
            $cardName = $translator->trans("SalesCard", [], Metabase::DOMAIN_NAME);
            $descriptionCard = $translator->trans("card with sales", [], Metabase::DOMAIN_NAME);
            $cardNameNumber = $translator->trans("Sale Number", [], Metabase::DOMAIN_NAME);
            $descriptionCardNumber = $translator->trans("card with the sale number", [], Metabase::DOMAIN_NAME);

            $dashboard = json_decode($metabaseService->createDashboard($dashboardName, $descriptionDashboard, $collectionId));

            $fieldDate = $metabaseService->searchField($fields, "Created At", "Order");
            $fieldOrderType = $metabaseService->searchField($fields, "Status ID", "Order");

            $defaultOrderType = $metabaseService->getDefaultOrderType();

            $query = "select SUM(ROUND(IF(op.`was_in_promo`, op.`promo_price` + opt.`promo_amount`, op.`price` + opt.`amount`), 2) * op.`quantity`) - SUM(ROUND(`order`.`discount`, 2)) + SUM(`order`.`postage`) as TOTAL, 
                    DATE_FORMAT(op.`created_at`, \"%m \") as Date 
                    from `order_product` as op 
                    join `order_product_tax` as opt on op.`id` = opt.`order_product_id` 
                    join `order` on `order`.`id` = op.`order_id`
                    where {{start}} [[and {{orderType}}]]
                    group by Date";

            $query2 = "select SUM(ROUND(IF(op.`was_in_promo`, op.`promo_price` + opt.`promo_amount`, op.`price` + opt.`amount`), 2) * op.`quantity`) - SUM(ROUND(`order`.`discount`, 2)) + SUM(`order`.`postage`) as TOTAL
                    from `order_product` as op 
                    join `order_product_tax` as opt on op.`id` = opt.`order_product_id` 
                    join `order` on `order`.`id` = op.`order_id`
                    where {{start}} [[and {{orderType}}]]";

            $card = json_decode($metabaseService->createCard(
                ["graph.dimensions" => ["Date"], "graph.metrics" => ["TOTAL"] ],
                [
                    [
                        "id" => "908503d9-269d-df89-d591-79a5d8810583",
                        "type" => "date/relative",
                        "target" => ["dimension", ["template-tag", "start"]],
                        "name" => "Start",
                        "slug" => "start",
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
                                "default" => "past1years",
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

            $card2 = json_decode($metabaseService->createCard(
                ["graph.dimensions" => ["Date"], "graph.metrics" => ["TOTAL"] ],
                [
                    [
                        "id" => "908503d9-269d-df89-d591-79a5d8810583",
                        "type" => "date/relative",
                        "target" => ["dimension", ["template-tag", "start"]],
                        "name" => "Start",
                        "slug" => "start",
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
                                "default" => "thisyear",
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
                ["graph.dimensions" => ["Date"], "graph.metrics" => ["TOTAL"] ],
                [
                    [
                        "id" => "908503d9-269d-df89-d591-79a5d8810583",
                        "type" => "date/relative",
                        "target" => ["dimension", ["template-tag", "start"]],
                        "name" => "Start",
                        "slug" => "start",
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
                $cardNameNumber."1",
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
                                "default" => "past1years",
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

            $card4 = json_decode($metabaseService->createCard(
                ["graph.dimensions" => ["Date"], "graph.metrics" => ["TOTAL"] ],
                [
                    [
                    "id" => "908503d9-269d-df89-d591-79a5d8810583",
                    "type" => "date/relative",
                    "target" => ["dimension", ["template-tag", "start"]],
                    "name" => "Start",
                    "slug" => "start",
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
                $cardNameNumber."2",
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
                                "default" => "thisyear",
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
            $dashboardCard3 = json_decode($metabaseService->addCardToDashboard($dashboard->id, $card4->id));

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
                    "parameter_id" => "5ef8a7ef",
                    "card_id" => $card2->id,
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
                    "parameter_id" => "5ef8a7ef",
                    "card_id" => $card4->id,
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
                    "card_id" => $card2->id,
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
                [
                    "parameter_id" => "64b9491",
                    "card_id" => $card4->id,
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
            $metabaseService->resizeCards($dashboard->id, $dashboardCard->id, $parameter_mappings, [$series], 0,0, 18, 4);
            $metabaseService->resizeCards($dashboard->id, $dashboardCard2->id, $parameter_mappings, [], 6,0, 9, 3);
            $metabaseService->resizeCards($dashboard->id, $dashboardCard3->id, $parameter_mappings, [],6,9, 9, 3);

            $parameters = [
                [
                    "name" => "Date 1",
                    "slug" => "date_1",
                    "id" => "5ef8a7ee",
                    "type" => "date/all-options",
                    "sectionId" => "date",
                    "default" => "thisyear"
                ],
                [
                    "name" => "Date 2",
                    "slug" => "date_2",
                    "id" => "5ef8a7ef",
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

            $metabaseService->publishDashboard($dashboard->id, ["date_1" => "enabled", "date_2" => "enabled", "order_type" => "enabled"], $parameters);
        }
    }