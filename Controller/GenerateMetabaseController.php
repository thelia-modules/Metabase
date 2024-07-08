<?php

namespace Metabase\Controller;

use Metabase\Event\MetabaseStatisticEvent;
use Metabase\Event\MetabaseStatisticEvents;
use Metabase\Exception\MetabaseException;
use Metabase\Form\GenerateMetabase;
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
use Thelia\Model\LangQuery;
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

        $langs = LangQuery::create()->filterByActive(1)->find();

        foreach ($langs as $lang) {
            $locale = $lang->getLocale();

            // delete old Collection
            if (null !== Metabase::getConfigValue(Metabase::METABASE_COLLECTION_ROOT_ID_CONFIG_KEY.'_'.$locale)) {
                $this->metabaseAPIService->deleteCollection(Metabase::getConfigValue(Metabase::METABASE_COLLECTION_ROOT_ID_CONFIG_KEY.'_'.$locale));
            }

            $rootCollectionName = Metabase::getConfigValue(Metabase::METABASE_NAME_CONFIG_KEY).'_'.$locale;

            $monthlyRevenueCollectionName = $translator?->trans('MonthlyRevenueCollection', [], Metabase::DOMAIN_NAME, $locale);
            $annualRevenueCollectionName = $translator?->trans('AnnualRevenueCollection', [], Metabase::DOMAIN_NAME, $locale);
            $bestSellerCollectionName = $translator?->trans('BestSellerCollection', [], Metabase::DOMAIN_NAME, $locale);

            $brandCollectionName = $translator?->trans('BrandCollection', [], Metabase::DOMAIN_NAME, $locale);
            $categoryCollectionName = $translator?->trans('CategoryCollection', [], Metabase::DOMAIN_NAME, $locale);
            $productCollectionName = $translator?->trans('ProductCollection', [], Metabase::DOMAIN_NAME, $locale);

            $rootCollection = $this->metabaseAPIService->createCollection(['name' => $rootCollectionName]);
            Metabase::setConfigValue(Metabase::METABASE_COLLECTION_ROOT_ID_CONFIG_KEY.'_'.$locale, $rootCollection->id);

            $fields = $this->metabaseAPIService->getAllField();

            try {
                $monthlyRevenueCollection = $monthlyRevenueStatisticMetabaseService->generateCollection($monthlyRevenueCollectionName, $rootCollection->id);
                $monthlyRevenueStatisticMetabaseService->generateStatisticMetabase($monthlyRevenueCollection->id, $fields, $locale);

                $annualRevenueCollection = $annualRevenueStatisticMetabaseService->generateCollection($annualRevenueCollectionName, $rootCollection->id);
                $annualRevenueStatisticMetabaseService->generateStatisticMetabase($annualRevenueCollection->id, $fields, $locale);

                $bestSellerCollection = $bestSellerStatisticMetabaseService->generateCollection($bestSellerCollectionName, $rootCollection->id);
                $bestSellerStatisticMetabaseService->generateStatisticMetabase($bestSellerCollection->id, $fields, $locale);

                if (!Metabase::getConfigValue(Metabase::METABASE_DISABLE_BRAND_CONFIG_KEY)) {
                    $brandCollection = $brandStatisticMetabaseService->generateCollection($brandCollectionName, $rootCollection->id);
                    $brandStatisticMetabaseService->generateStatisticMetabase($brandCollection->id, $fields, $locale);
                }

                if (!Metabase::getConfigValue(Metabase::METABASE_DISABLE_CATEGORY_CONFIG_KEY)) {
                    $categoryCollection = $categoryStatisticMetabaseService->generateCollection($categoryCollectionName, $rootCollection->id);
                    $categoryStatisticMetabaseService->generateStatisticMetabase($categoryCollection->id, $fields, $locale);
                }

                if (!Metabase::getConfigValue(Metabase::METABASE_DISABLE_CATEGORY_CONFIG_KEY)) {
                    $productCollection = $productStatisticMetabaseService->generateCollection($productCollectionName, $rootCollection->id);
                    $productStatisticMetabaseService->generateStatisticMetabase($productCollection->id, $fields, $locale);
                }

                $event = new MetabaseStatisticEvent($rootCollection->id, $fields, $locale);
                $eventDispatcher->dispatch($event, MetabaseStatisticEvents::ADD_METABASE_STATISTICS);
            } catch (MetabaseException $e) {
                return $this->generateRedirect(URL::getInstance()?->absoluteUrl('/admin/module/Metabase', ['tab' => 'generate', 'error_message' => $e->getMessage()]));
            }
        }

        return $this->generateRedirect(URL::getInstance()?->absoluteUrl('/admin/module/Metabase', ['tab' => 'generate', 'success' => 1]));
    }
}
