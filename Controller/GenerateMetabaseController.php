<?php

namespace Metabase\Controller;

use Metabase\Event\MetabaseStatisticEvent;
use Metabase\Event\MetabaseStatisticEvents;
use Metabase\Exception\MetabaseException;
use Metabase\Form\GenerateMetabase;
use Metabase\Form\ImportMetabase;
use Metabase\Form\SyncingMetabase;
use Metabase\Metabase;
use Metabase\Service\AnnualRevenueStatisticMetabaseService;
use Metabase\Service\API\MetabaseAPIService;
use Metabase\Service\BestSellerStatisticMetabaseService;
use Metabase\Service\BrandStatisticMetabaseService;
use Metabase\Service\CategoryStatisticMetabaseService;
use Metabase\Service\MonthlyRevenueStatisticMetabaseService;
use Metabase\Service\ProductStatisticMetabaseService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

#[Route('/admin/module/Metabase', name: 'admin_metabase_generate_')]
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
    #[Route('/import_database', name: 'import_database', methods: ['POST'])]
    public function ImportDatabaseMetabase()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ImportMetabase::getName());

        try {
            $data = $this->validateForm($form)->getData();

            $database = $this->metabaseAPIService->importDatabase(
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
            Metabase::setConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY, $database->id);

            return $this->generateSuccessRedirect($form);
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

        try {
            $data = $this->validateForm($form)->getData();

            Metabase::setConfigValue(Metabase::METABASE_ORDER_TYPE_CONFIG_KEY, implode(',', $data['order_type']));
            Metabase::setConfigValue(Metabase::METABASE_DISABLE_BRAND_CONFIG_KEY, $data['disable_brand']);
            Metabase::setConfigValue(Metabase::METABASE_DISABLE_CATEGORY_CONFIG_KEY, $data['disable_category']);
            Metabase::setConfigValue(Metabase::METABASE_DISABLE_PRODUCT_CONFIG_KEY, $data['disable_product']);

            $state = $this->metabaseAPIService->checkMetabaseState();

            if ('complete' === $state->initial_sync_status) {
                return $this->generateRedirect(URL::getInstance()?->absoluteUrl('/admin/module/Metabase/generate'));
            }

            return $this->generateSuccessRedirect($form);
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
        MonthlyRevenueStatisticMetabaseService $monthlyRevenueStatisticMetabaseService,
        AnnualRevenueStatisticMetabaseService $annualRevenueStatisticMetabaseService,
        BestSellerStatisticMetabaseService $bestSellerStatisticMetabaseService,
        BrandStatisticMetabaseService $brandStatisticMetabaseService,
        CategoryStatisticMetabaseService $categoryStatisticMetabaseService,
        ProductStatisticMetabaseService $productStatisticMetabaseService,
        EventDispatcherInterface $eventDispatcher
    ) {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $translator = Translator::getInstance();
        $rootCollectionName = Metabase::getConfigValue(Metabase::METABASE_NAME_CONFIG_KEY);

        $monthlyRevenueCollectionName = $translator?->trans('MonthlyRevenueCollection', [], Metabase::DOMAIN_NAME);
        $annualRevenueCollectionName = $translator?->trans('AnnualRevenueCollection', [], Metabase::DOMAIN_NAME);
        $bestSellerCollectionName = $translator?->trans('BestSellerCollection', [], Metabase::DOMAIN_NAME);

        $brandCollectionName = $translator?->trans('BrandCollection', [], Metabase::DOMAIN_NAME);
        $categoryCollectionName = $translator?->trans('CategoryCollection', [], Metabase::DOMAIN_NAME);
        $productCollectionName = $translator?->trans('ProductCollection', [], Metabase::DOMAIN_NAME);

        $rootCollection = $this->metabaseAPIService->createCollection(['name' => $rootCollectionName]);
        Metabase::setConfigValue(Metabase::METABASE_COLLECTION_ROOT_ID_CONFIG_KEY, $rootCollection->id);

        $fields = $this->metabaseAPIService->getAllField();

        try {
            $monthlyRevenueCollection = $monthlyRevenueStatisticMetabaseService->generateCollection($monthlyRevenueCollectionName, $rootCollection->id);
            $monthlyRevenueStatisticMetabaseService->generateStatisticMetabase($monthlyRevenueCollection->id, $fields);

            $annualRevenueCollection = $annualRevenueStatisticMetabaseService->generateCollection($annualRevenueCollectionName, $rootCollection->id);
            $annualRevenueStatisticMetabaseService->generateStatisticMetabase($annualRevenueCollection->id, $fields);

            $bestSellerCollection = $bestSellerStatisticMetabaseService->generateCollection($bestSellerCollectionName, $rootCollection->id);
            $bestSellerStatisticMetabaseService->generateStatisticMetabase($bestSellerCollection->id, $fields);

            if (!Metabase::getConfigValue(Metabase::METABASE_DISABLE_BRAND_CONFIG_KEY)) {
                $brandCollection = $brandStatisticMetabaseService->generateCollection($brandCollectionName, $rootCollection->id);
                $brandStatisticMetabaseService->generateStatisticMetabase($brandCollection->id, $fields);
            }

            if (!Metabase::getConfigValue(Metabase::METABASE_DISABLE_CATEGORY_CONFIG_KEY)) {
                $categoryCollection = $categoryStatisticMetabaseService->generateCollection($categoryCollectionName, $rootCollection->id);
                $categoryStatisticMetabaseService->generateStatisticMetabase($categoryCollection->id, $fields);
            }

            if (!Metabase::getConfigValue(Metabase::METABASE_DISABLE_CATEGORY_CONFIG_KEY)) {
                $productCollection = $productStatisticMetabaseService->generateCollection($productCollectionName, $rootCollection->id);
                $productStatisticMetabaseService->generateStatisticMetabase($productCollection->id, $fields);
            }

            $event = new MetabaseStatisticEvent($fields, $rootCollection->id);
            $eventDispatcher->dispatch($event, MetabaseStatisticEvents::ADD_METABASE_STATISTICS);
        } catch (MetabaseException $e) {
            return $this->generateRedirect(URL::getInstance()?->absoluteUrl('/admin/module/Metabase', ['tab' => 'generate', 'error_message' => $e->getMessage()]));
        }

        return $this->generateRedirect(URL::getInstance()?->absoluteUrl('/admin/module/Metabase', ['tab' => 'generate', 'success' => 1]));
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

            return $this->generateRedirect(URL::getInstance()?->absoluteUrl('/admin/module/Metabase', ['tab' => 'syncing', 'syncing' => 1]));
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
