<?php

namespace Metabase\Controller;

use Metabase\Metabase;
use Metabase\Service\MetabaseService;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Statistic\Statistic;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Map\OrderProductTableMap;
use Thelia\Model\Map\ProductTableMap;
use Thelia\Model\OrderQuery;

class StatisticBrandController extends AdminController
{
    /**
     * @param MetabaseService $metabaseService
     * @param int $databaseId
     * @param int $collectionId
     * @param bool $count
     * true == Create a dashboard and cards with brands numbers of sales for a selected date /
     * false == Create a dashboard and cards with brands sales for a selected date
     */
    public function generateBrandStatisticsMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId, array $fields, bool $count = false)
    {
        $translator = Translator::getInstance();
        $dashboardName = $translator->trans("Dashboard Sales Brand", [], Metabase::DOMAIN_NAME);
        $descriptionDashboard = $translator->trans("Sales Statistic by Brand", [], Metabase::DOMAIN_NAME);
        $cardName = $translator->trans("BrandSalesCard_", [], Metabase::DOMAIN_NAME);
        $descriptionCard = $translator->trans("card of Sales Statistic by Brand", [], Metabase::DOMAIN_NAME);
        $column_settings = [
            "[\"name\",\"TOTAL\"]" => [
                "suffix" => "€",
                "number_separators" => ", "
            ]
        ];

        $uuidBrand = uniqid();
        $uuidDate = uniqid();
        $uuidOrderType = uniqid();
        $uuidParamBrand = uniqid();
        $uuidParamDate1 = uniqid();
        $uuidParamDate2 = uniqid();
        $uuidParamOrderType = uniqid();

        $query = "SELECT SUM((`order_product`.QUANTITY * IF(`order_product`.WAS_IN_PROMO,`order_product`.PROMO_PRICE,`order_product`.PRICE))) AS TOTAL, 
            DATE_FORMAT(`order`.`invoice_date`,\"%b\") as DATE
            FROM `order` 
            INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
            INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
            INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
            WHERE 1=0 [[or {{brand}}]] and {{date}} [[and {{orderType}}]]
            GROUP BY date";


        if ($count){
            $dashboardName = $translator->trans("Dashboard Count Brand", [], Metabase::DOMAIN_NAME);
            $descriptionDashboard = $translator->trans("Count Statistic by Brand", [], Metabase::DOMAIN_NAME);
            $cardName = $translator->trans("BrandCard_", [], Metabase::DOMAIN_NAME);
            $descriptionCard = $translator->trans("card of Count Statistic by Brand", [], Metabase::DOMAIN_NAME);
            $column_settings = [];

            $query = "SELECT SUM(order_product.quantity) AS TOTAL, 
                DATE_FORMAT(`order`.`invoice_date`,\"%b\") as DATE
                FROM `order` 
                INNER JOIN `order_product` ON (`order`.`id`=`order_product`.`order_id`) 
                INNER JOIN `product` ON (`order_product`.`product_ref`=`product`.`ref`)
                INNER JOIN `brand_i18n` ON `brand_i18n`.`id`=`product`.`brand_id`
                WHERE 1=0 [[or {{brand}}]] and {{date}} [[and {{orderType}}]]
                GROUP BY DATE";
        }

        $dashboard = json_decode($metabaseService->createDashboard($dashboardName, $descriptionDashboard, $collectionId));

        $fieldBrandRef = $metabaseService->searchField($fields, "Title", "Brand I18n");
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
                    "id" => $uuidBrand,
                    "type" => "string/=",
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "brand"
                        ]
                    ],
                    "name" => "Brand",
                    "slug" => "brand",
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
                        "brand" => [
                            "id" => $uuidBrand,
                            "name" => "brand",
                            "display-name" => "Brand",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldBrandRef,
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
                "graph.metrics" => ["brand"],
                "column_settings" => $column_settings
            ],
            [
                [
                    "id" => $uuidBrand,
                    "type" => "string/=",
                    "target" => [
                        "dimension",
                        [
                            "template-tag",
                            "brand"
                        ]
                    ],
                    "name" => "Brand",
                    "slug" => "brand",
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
                        "brand" => [
                            "id" => $uuidBrand,
                            "name" => "brand",
                            "display-name" => "Brand",
                            "type" => "dimension",
                            "dimension" => [
                                "field",
                                $fieldBrandRef,
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
                "parameter_id" => $uuidParamBrand,
                "card_id" => $card2->id,
                "target" => [
                    "dimension",
                    [
                        "template-tag",
                        "brand"
                    ]
                ]
            ],
            [
                "parameter_id" => $uuidParamBrand,
                "card_id" => $card->id,
                "target" => [
                    "dimension",
                    [
                        "template-tag",
                        "brand"
                    ]
                ]
            ],
            [
                "parameter_id" => "$uuidParamOrderType",
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
                "name" => "Brand Reference",
                "slug" => "brand_reference",
                "id" => $uuidParamBrand,
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
            ["date_1" => "enabled", "date_2" => "enabled", "brand_reference" => "enabled", "orderType" => "enabled"], $parameters);
    }
}