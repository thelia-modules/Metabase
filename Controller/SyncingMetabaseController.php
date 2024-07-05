<?php

namespace Metabase\Controller;

use Metabase\Form\SyncingMetabase;
use Metabase\Metabase;
use Metabase\Service\API\MetabaseAPIService;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\ParserContext;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;

#[Route('/admin/module/Metabase', name: 'admin_metabase_syncing_')]
class SyncingMetabaseController extends AdminController
{
    public function __construct(
        protected MetabaseAPIService $metabaseAPIService,
        protected ParserContext $parserContext
    ) {
    }

    #[Route('/syncing', name: 'parameters', methods: ['POST'])]
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
