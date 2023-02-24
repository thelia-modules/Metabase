<?php

namespace Metabase\Controller;

use Metabase\Form\GenerateMetabase;
use Metabase\Form\ImportMetabase;
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

        $form = $this->createForm(ImportMetabase::getName());

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

        $form = $this->createForm(GenerateMetabase::getName());

        $url = '/admin/module/Metabase';
        try {
            $data = $this->validateForm($form)->getData();
            Metabase::setConfigValue(Metabase::CONFIG_METABASE_ORDER_TYPE, $data["order_type"]);

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
    public function generateMetabase(
        Request $request,
        MetabaseService $metabaseService,
        MainStatisticMetabaseController $mainStatisticMetabaseController,
        SalesMetabaseController $salesMetabaseController,
        StatisticProductController $statisticProductsController,
        StatisticCategoryController $statisticCategoryController,
        StatisticBrandController $statisticBrandController,
        StatisticBestSellerController $bestSellerController
    ){
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $url = '/admin/module/Metabase';

        $translator = Translator::getInstance();
        $rootCollectionName = $translator->trans("RootCollection", [], Metabase::DOMAIN_NAME);
        $mainCollectionName = $translator->trans("MainCollection", [], Metabase::DOMAIN_NAME);
        $statCollectionName = $translator->trans("StatCollection", [], Metabase::DOMAIN_NAME);

        $rootCollection = json_decode($this->generateCollection($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $rootCollectionName));
        $mainCollection = json_decode($this->generateCollection($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $mainCollectionName, $rootCollection->id));
        $statisticCollection = json_decode($this->generateCollection($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $statCollectionName, $rootCollection->id));

        $fields = json_decode($metabaseService->getAllField(Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID)));

        $mainStatisticMetabaseController->generateMainStatisticMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $mainCollection->id, $fields);
        $salesMetabaseController->generateSaleMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $mainCollection->id, $fields);
        $bestSellerController->generateBestSellerStatisticsMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $mainCollection->id, $fields);

        $statisticProductsController->generateProductStatisticsMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $statisticCollection->id, $fields);
        $statisticProductsController->generateProductStatisticsMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $statisticCollection->id, $fields, true);

        $statisticCategoryController->generateCategoryStatisticsMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $statisticCollection->id, $fields);
        $statisticCategoryController->generateCategoryStatisticsMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $statisticCollection->id, $fields, true);

        $statisticBrandController->generateBrandStatisticsMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $statisticCollection->id, $fields);
        $statisticBrandController->generateBrandStatisticsMetabase($metabaseService, Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_ID), $statisticCollection->id, $fields,true);

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'metabasesuccess' => 1]));
    }

    private function addBddMetabase(MetabaseService $metabaseService, String $metabaseName, String $dbName, String $engine, String $host, String $port, String $user, String $password="")
    {
        return $metabaseService->importBDD($metabaseName, $dbName, $engine, $host, $port, $user, $password);
    }

    private function generateCollection(MetabaseService $metabaseService, int $databaseId, String $name, int $parentId = null)
    {
        return $metabaseService->createCollection($databaseId, $name, $parentId);
    }
}