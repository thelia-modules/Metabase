<?php

    namespace Metabase\Controller;

    use Metabase\Service\MetabaseService;
    use Thelia\Controller\Admin\AdminController;
    use Thelia\Core\Security\AccessManager;
    use Thelia\Core\Security\Resource\AdminResources;

    class SalesMetabaseController extends AdminController
    {
        /**
         * Creer un tableau avec le chiffre d'affaires du magasin
         */
        public function generateSaleMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId)
        {
            $dashboard = json_decode($metabaseService->createDashboard("SalesDashboard", "sales of the store", $collectionId));

            $jsonFields = $metabaseService->getAllField($databaseId);

            $field = $metabaseService->searchField(json_decode($jsonFields), "Created At", "Order");

            $query = "select SUM(ROUND(IF(op.`was_in_promo`, op.`promo_price` + opt.`promo_amount`, op.`price` + opt.`amount`), 2) * op.`quantity`) - SUM(ROUND(`order`.`discount`, 2)) + SUM(`order`.`postage`) as TOTAL, 
                    DATE_FORMAT(op.`created_at`, \"%m \") as Date 
                    from `order_product` as op 
                    join `order_product_tax` as opt on op.`id` = opt.`order_product_id` 
                    join `order` on `order`.`id` = op.`order_id`
                    where {{start}} 
                    group by Date";

            $query2 = "select SUM(ROUND(IF(op.`was_in_promo`, op.`promo_price` + opt.`promo_amount`, op.`price` + opt.`amount`), 2) * op.`quantity`) - SUM(ROUND(`order`.`discount`, 2)) + SUM(`order`.`postage`) as TOTAL
                    from `order_product` as op 
                    join `order_product_tax` as opt on op.`id` = opt.`order_product_id` 
                    join `order` on `order`.`id` = op.`order_id`
                    where {{start}}";

            $card = json_decode($metabaseService->createCard(
                ["graph.dimensions" => ["Date"], "graph.metrics" => ["TOTAL"] ],
                [[
                    "id" => "908503d9-269d-df89-d591-79a5d8810583",
                    "type" => "date/relative",
                    "target" => ["dimension", ["template-tag", "start"]],
                    "name" => "Start",
                    "slug" => "start",
                    "default" => "past1years"
                ]],
                "SalesCard",
                "card of all the sales of the store",
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
                                    $field,
                                    null
                                ],
                                "widget-type" => "date/relative",
                                "default" => "past1years",
                                "required" => true
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
                [[
                    "id" => "908503d9-269d-df89-d591-79a5d8810583",
                    "type" => "date/relative",
                    "target" => ["dimension", ["template-tag", "start"]],
                    "name" => "Start",
                    "slug" => "start",
                    "default" => "thisyear"
                ]],
                "SalesCard",
                "card of all the sales of the store",
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
                                    $field,
                                    null
                                ],
                                "widget-type" => "date/relative",
                                "default" => "thisyear",
                                "required" => true
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
                [[
                    "id" => "908503d9-269d-df89-d591-79a5d8810583",
                    "type" => "date/relative",
                    "target" => ["dimension", ["template-tag", "start"]],
                    "name" => "Start",
                    "slug" => "start",
                    "default" => "past1years"
                ]],
                "SalesCardNumber",
                "card sales of the store",
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
                                    $field,
                                    null
                                ],
                                "widget-type" => "date/relative",
                                "default" => "past1years",
                                "required" => true
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
                [[
                    "id" => "908503d9-269d-df89-d591-79a5d8810583",
                    "type" => "date/relative",
                    "target" => ["dimension", ["template-tag", "start"]],
                    "name" => "Start",
                    "slug" => "start",
                    "default" => "thisyear"
                ]],
                "SalesCardNumber",
                "card sales of the store",
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
                                    $field,
                                    null
                                ],
                                "widget-type" => "date/relative",
                                "default" => "thisyear",
                                "required" => true
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
                ]];

            $metabaseService->publishDashboard($dashboard->id, ["date_1" => "enabled", "date_2" => "enabled"], $parameters);
        }
    }