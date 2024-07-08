<?php

namespace Metabase\Controller;

use Metabase\Form\ConnectMetabase;
use Metabase\Metabase;
use Metabase\Service\API\MetabaseAPIService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\ParserContext;
use Thelia\Form\Exception\FormValidationException;

#[Route('/admin/module/Metabase', name: 'admin_metabase_connect_')]
class ConnectMetabaseController extends AdminController
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
    #[Route('/connect_database', name: 'database', methods: ['POST'])]
    public function ConnectDatabaseMetabase()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ConnectMetabase::getName());

        try {
            $data = $this->validateForm($form)->getData();

            Metabase::setConfigValue(Metabase::METABASE_NAME_CONFIG_KEY, $data['metabaseName']);
            Metabase::setConfigValue(Metabase::METABASE_DB_NAME_CONFIG_KEY, $data['dbName']);
            Metabase::setConfigValue(Metabase::METABASE_ENGINE_CONFIG_KEY, $data['engine']);
            Metabase::setConfigValue(Metabase::METABASE_HOST_CONFIG_KEY, $data['host']);
            Metabase::setConfigValue(Metabase::METABASE_PORT_CONFIG_KEY, $data['port']);
            Metabase::setConfigValue(Metabase::METABASE_DB_USERNAME_CONFIG_KEY, $data['user']);

            if (null !== $oldDbId = Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY)) {
                $this->metabaseAPIService->deleteDatabase($oldDbId);
            }

            $database = $this->metabaseAPIService->connectDatabase(
                $data['dbName'],
                $data['dbName'],
                $data['engine'],
                $data['host'],
                $data['port'],
                $data['user'],
                $data['password'] ?? '',
            );

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
}
