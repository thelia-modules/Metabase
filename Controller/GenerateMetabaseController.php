<?php

namespace Metabase\Controller;

use Metabase\Form\GenerateMetabase;
use Metabase\Metabase;
use Metabase\Service\MetabaseService;
use Thelia\Controller\Admin\AdminController;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;

class GenerateMetabaseController extends AdminController
{
    /**
     * @Route("/admin/module/metabase/importbdd", name="metabase.importbdd", methods="post")
     */
    public function ImportBddMetabase(Request $request, MetabaseService $metabaseService){

        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(GenerateMetabase::getName());

        $url = '/admin/module/Metabase';

        try {
            $data = $this->validateForm($form)->getData();

            $jsonBdd = $this->addBddMetabase(
                $metabaseService,
                $data["metabaseName"],
                $data["dbName"],
                $data["engine"],
                $data["host"],
                $data["port"],
                $data["user"],
                $data["password"] ?? "",
            );

            $bdd = json_decode($jsonBdd);

            Metabase::setConfigValue(Metabase::CONFIG_METABASE_NAME, $data["metabaseName"]);
            Metabase::setConfigValue(Metabase::CONFIG_METABASE_DB_NAME, $data["dbName"]);
            Metabase::setConfigValue(Metabase::CONFIG_METABASE_ENGINE, $data["engine"]);
            Metabase::setConfigValue(Metabase::CONFIG_METABASE_HOST, $data["host"]);
            Metabase::setConfigValue(Metabase::CONFIG_METABASE_PORT, $data["port"]);
            Metabase::setConfigValue(Metabase::CONFIG_METABASE_DB_USERNAME, $data["user"]);
            Metabase::setConfigValue(Metabase::CONFIG_METABASE_DB_ID, $bdd->id);

        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans('Metabase generation work in progress '),
                $message = $e->getMessage(),
                $form,
                $e
            );
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'wait' => 1]));
    }

    /**
     * @Route("/admin/module/metabase/check", name="metabase.check")
     */
    public function checkMetabase(Request $request, MetabaseService $metabaseService){
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $url = '/admin/module/Metabase';
        try {
            $state = $metabaseService->checkMetabaseState(
                Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID)
            );

            if (json_decode($state)->initial_sync_status === "incomplete") {
                return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'wait' => 1]));
            }

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'ready' => 1]));
        } catch (\Exception $exception){
            $this->errorPage($exception->getMessage());
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'error' => $exception->getMessage()]));
    }

    /**
     * @Route("/admin/module/metabase/generate", name="metabase.generate")
     */
    public function generateMetabase(Request $request, MetabaseService $metabaseService){

        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $url = '/admin/module/Metabase';

        $jsonCollection = $this->generateCollection($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID));

        $collection = json_decode($jsonCollection);

        $this->generateSaleMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $collection->id);

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'metabasesuccess' => 1]));
    }

    /**
     * Creer un tableau avec le chiffre d'affaires du magasin
     */
    public function generateSaleMetabase(MetabaseService $metabaseService, int $databaseId, int $collectionId)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $jsonDashboard = $metabaseService->createDashboard("SalesDashboard", "sales of the store", $collectionId);
        $dashboard = json_decode($jsonDashboard);

        $jsonFields = $metabaseService->getAllField($databaseId);

        $field = $this->searchField(json_decode($jsonFields), "Created At", "Order");

        $query = "select SUM(ROUND(IF(op.`was_in_promo`, op.`promo_price` + opt.`promo_amount`, op.`price` + opt.`amount`), 2) * op.`quantity`) - SUM(ROUND(`order`.`discount`, 2)) + SUM(`order`.`postage`) as CA, DATE_FORMAT(op.`created_at`, \"%Y-%m-%d \") as Date from `order_product` as op join `order_product_tax` as opt on op.`id` = opt.`order_product_id` join `order` on `order`.`id` = op.`order_id`[[where {{start}}]] group by Date";

        $jsonCard = $metabaseService->createCard(
            ["graph.dimensions" => ["Date"], "graph.metrics" => ["CA"] ],
            [
                "id" => "908503d9-269d-df89-d591-79a5d8810583",
                "type" => "date/relative",
                "target" => ["dimension", ["template-tag", "start"]],
                "name" => "Start",
                "slug" => "start",
                "default" => "past1weeks"
            ],
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
                            "default" => "past1weeks",
                            "required" => true
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
        $jsonAddCard = $metabaseService->addCardToDashboard($dashboard->id, $card->id);

        $dashboardCard = json_decode($jsonAddCard);

        $metabaseService->resizeCards($dashboard->id, $dashboardCard->id, $card->id);

        $metabaseService->publishDashboard($dashboard->id, ["relative_date" => "enabled"]);
    }

    private function searchField(Array $fields, String $fieldName, String $tableName){

        foreach ($fields as $field){
            if ($field->name === $fieldName && $field->table_name === $tableName){
                return $field->id;
            }
        }
        return null;
    }

    private function addBddMetabase(MetabaseService $metabaseService, String $metabaseName, String $dbName, String $engine, String $host, String $port, String $user, String $password="")
    {
        return $metabaseService->importBDD($metabaseName, $dbName, $engine, $host, $port, $user, $password);
    }

    private function generateCollection(MetabaseService $metabaseService, int $databaseId)
    {
        return $metabaseService->createCollection($databaseId);
    }
}