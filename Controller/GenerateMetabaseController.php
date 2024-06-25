<?php

namespace Metabase\Controller;

use Metabase\Exception\MetabaseException;
use Metabase\Form\GenerateMetabase;
use Metabase\Form\ImportMetabase;
use Metabase\Form\SyncingMetabase;
use Metabase\Metabase;
use Metabase\Service\API\MetabaseAPIService;
use Metabase\Service\MainStatisticMetabaseService;
use Metabase\Service\SalesMetabaseService;
use Metabase\Service\StatisticBestSellerService;
use Metabase\Service\StatisticBrandService;
use Metabase\Service\StatisticCategoryService;
use Metabase\Service\StatisticProductService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;

#[Route('/admin/module/Metabase', name: 'admin_metabase_bdd_')]
class GenerateMetabaseController extends AdminController
{
    public function __construct(
        protected MetabaseAPIService $metabaseAPIService,
        protected ParserContext $parserContext
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/importbdd', name: 'importbdd', methods: ['POST'])]
    public function ImportBddMetabase()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ImportMetabase::getName());

        $url = '/admin/module/Metabase';

        try {
            $data = $this->validateForm($form)->getData();

            $bdd = $this->metabaseAPIService->importBDD(
                $data['dbName'],
                $data['dbName'],
                $data['engine'],
                $data['host'],
                $data['port'],
                $data['user'],
                $data['password'] ?? '',
            );

            Metabase::setConfigValue(Metabase::METABASE_NAME_CONFIG_KEY, $data['metabaseName']);
            Metabase::setConfigValue(Metabase::METABASE_DB_NAME_CONFIG_KEY, $data['dbName']);
            Metabase::setConfigValue(Metabase::METABASE_ENGINE_CONFIG_KEY, $data['engine']);
            Metabase::setConfigValue(Metabase::METABASE_HOST_CONFIG_KEY, $data['host']);
            Metabase::setConfigValue(Metabase::METABASE_PORT_CONFIG_KEY, $data['port']);
            Metabase::setConfigValue(Metabase::METABASE_DB_USERNAME_CONFIG_KEY, $data['user']);
            Metabase::setConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY, $bdd->id);

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'wait' => 1]));
        } catch (FormValidationException $e) {
            $error_message = $this->createStandardFormValidationErrorMessage($e);
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
        }

        $form->setErrorMessage($error_message);

        $this->parserContext
            ->addForm($form)
            ->setGeneralError($error_message);

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'import', 'wait' => 1]));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/check', name: 'check', methods: ['POST'])]
    public function checkMetabase()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(GenerateMetabase::getName());

        $url = '/admin/module/Metabase';
        try {
            $data = $this->validateForm($form)->getData();
            Metabase::setConfigValue(Metabase::METABASE_ORDER_TYPE_CONFIG_KEY, $data['order_type']);

            $state = $this->metabaseAPIService->checkMetabaseState();

            if ('incomplete' === $state->initial_sync_status) {
                return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'wait' => 1]));
            }

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'ready' => 1]));
        } catch (FormValidationException $e) {
            $error_message = $this->createStandardFormValidationErrorMessage($e);
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
        }

        $form->setErrorMessage($error_message);

        $this->parserContext
            ->addForm($form)
            ->setGeneralError($error_message);

        return $this->generateErrorRedirect($form);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MetabaseException
     */
    #[Route('/generate', name: 'generate')]
    public function generateMetabase(
        MainStatisticMetabaseService $mainStatisticMetabaseService,
        SalesMetabaseService $salesMetabaseService,
        StatisticProductService $statisticProductsService,
        StatisticCategoryService $statisticCategoryService,
        StatisticBrandService $statisticBrandService,
        StatisticBestSellerService $bestSellerService
    ) {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $url = '/admin/module/Metabase';

        $translator = Translator::getInstance();
        $rootCollectionName = Metabase::getConfigValue(Metabase::METABASE_NAME_CONFIG_KEY);
        $mainCollectionName = $translator->trans('MainCollection', [], Metabase::DOMAIN_NAME);
        $statCollectionName = $translator->trans('StatCollection', [], Metabase::DOMAIN_NAME);

        $rootCollection = $this->metabaseAPIService->createCollection($rootCollectionName);
        $mainCollection = $this->metabaseAPIService->createCollection($mainCollectionName, $rootCollection->id);
        $statisticCollection = $this->metabaseAPIService->createCollection($statCollectionName, $rootCollection->id);

        $fields = $this->metabaseAPIService->getAllField();

        $mainStatisticMetabaseService->generateStatisticMetabase($mainCollection->id, $fields);
        $salesMetabaseService->generateStatisticMetabase($mainCollection->id, $fields);
        $bestSellerService->generateStatisticMetabase($mainCollection->id, $fields);

        $statisticProductsService->generateStatisticMetabase($statisticCollection->id, $fields);
        $statisticCategoryService->generateStatisticMetabase($statisticCollection->id, $fields);
        $statisticBrandService->generateStatisticMetabase($statisticCollection->id, $fields);

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'generate', 'metabase_success' => 1]));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/syncing', name: 'syncing', methods: ['POST'])]
    public function updateSyncingParameterMetabase()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(SyncingMetabase::getName());

        $url = '/admin/module/Metabase';

        try {
            $data = $this->validateForm($form)->getData();

            $verifiedData = $this->metabaseAPIService->verifyFormSyncing($data);

            Metabase::setConfigValue(Metabase::METABASE_SYNCING_OPTION, $data['syncingOption']);
            Metabase::setConfigValue(Metabase::METABASE_SYNCING_SCHEDULE, $data['syncingSchedule']);
            Metabase::setConfigValue(Metabase::METABASE_SYNCING_TIME, $data['syncingTime']);
            Metabase::setConfigValue(Metabase::METABASE_SCANNING_SCHEDULE, $data['scanningSchedule']);
            Metabase::setConfigValue(Metabase::METABASE_SCANNING_TIME, $data['scanningTime']);
            Metabase::setConfigValue(Metabase::METABASE_SCANNING_FRAME, $data['scanningFrame']);
            Metabase::setConfigValue(Metabase::METABASE_SCANNING_DAY, $data['scanningDay']);
            Metabase::setConfigValue(Metabase::METABASE_REFINGERPRINT, $data['refingerprint']);

            $this->metabaseAPIService->updateSyncingParameters(
                Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY),
                $verifiedData['is_full_sync'],
                $verifiedData['is_on_demand'],
                $verifiedData['refingerprint'],
                $verifiedData['syncing_schedule'],
                $verifiedData['scanning_schedule'],
                $verifiedData['sync_hours'],
                $verifiedData['sync_minutes'],
                $verifiedData['scan_hours'],
                $verifiedData['scan_frame'],
                $verifiedData['scan_day'],
            );

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'syncing', 'syncing' => 1]));
        } catch (FormValidationException $e) {
            $error_message = $this->createStandardFormValidationErrorMessage($e);
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
        }

        $form->setErrorMessage($error_message);

        $this->parserContext
            ->addForm($form)
            ->setGeneralError($error_message);

        return $this->generateErrorRedirect($form);
    }
}
