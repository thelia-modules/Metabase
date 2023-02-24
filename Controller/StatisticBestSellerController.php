<?php

    namespace Metabase\Controller;

    use Metabase\Metabase;
    use Metabase\Service\MetabaseService;
    use Thelia\Controller\Admin\AdminController;
    use Thelia\Core\Translation\Translator;

    class StatisticBestSellerController extends AdminController
    {
        public function generateBestSellerStatisticsMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, array $fields)
        {
            $translator = Translator::getInstance();
            $dashboardName = $translator->trans("Dashboard Best Seller", [], Metabase::DOMAIN_NAME);
            $cardName = $translator->trans("BestSellerCard", [], Metabase::DOMAIN_NAME);
            $descriptionCard = $translator->trans("card Best Seller", [], Metabase::DOMAIN_NAME);

            $query = "SELECT SUM(`order_product`.`quantity`) AS TOTAL_SOLD,
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS TOTAL_HT,
                    SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product_tax`.PROMO_AMOUNT,`order_product_tax`.AMOUNT))) AS TAX,
                    `order_product`.`title` AS TITLE,
                    `order_product`.`product_ref` AS PRODUCT_REFERENCE, 
                    `order_product`.`product_sale_elements_id` AS PSE
                    FROM `order`
                    INNER JOIN `order_product` ON `order`.`id`=`order_product`.`order_id`
                    INNER JOIN `order_product_tax` ON `order_product`.`id`=`order_product_tax`.`order_product_id`
                    [[WHERE {{date}}]]
                    GROUP BY title, PRODUCT_REFERENCE, PSE
                    ORDER BY TOTAL_SOLD DESC";

            $dashboard = json_decode($metabaseService->createDashboard($dashboardName, "Best Seller dashboard", $collectionId));

            $fieldDate = $metabaseService->searchField($fields, "Created At", "Order");

            $card = json_decode($metabaseService->createCard(
                ["graph.dimensions" => ["Date"],
                    "graph.series_order_dimension" => null,
                    "graph.series_order" => null,
                    "column_settings" => [
                        "[\"name\",\"total_ht\"]" =>
                            [
                                "suffix" => "€",
                                "number_separators" => ", "
                            ],
                        "[\"name\",\"tax\"]" =>
                            [
                                "suffix" => "€",
                                "number_separators" => ", "
                            ]
                    ],
                    "graph.metrics" => ["category"]
                ],
                [
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
                $cardName,
                $descriptionCard,
                [
                    "database" => $databaseId,
                    "native" => [
                        "template-tags" =>  [
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
                "table",
                $collectionId
            ));

            $dashboardCard = json_decode($metabaseService->addCardToDashboard($dashboard->id, $card->id));

            $parameter_mappings = [
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
                ]
            ];

            $metabaseService->resizeCards($dashboard->id, $dashboardCard->id, $parameter_mappings);

            $parameters = [
                [
                    "name" => "Date",
                    "slug" => "date",
                    "id" => "44928ac5",
                    "type" => "date/all-options",
                    "sectionId" => "date",
                    "default" => "thisyear"
                ]];

            $metabaseService->publishDashboard($dashboard->id, ["date" => "enabled"], $parameters);
        }
    }