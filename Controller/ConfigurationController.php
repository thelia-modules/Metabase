<?php

namespace Metabase\Controller;

use Metabase\Exception\MetabaseException;
use Metabase\Form\ConfigureMetabase;
use Metabase\Metabase;
use Metabase\Service\MetabaseService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;

#[Route('/admin/module/Metabase/configure', name: 'admin_metabase_configure_')]
class ConfigurationController extends AdminController
{
    public function renderConfigPageAction(): Response|RedirectResponse
    {
        return $this->render('module-configure', ['module_code' => 'Metabase']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws MetabaseException
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function getDashboards(MetabaseService $metabaseService): Response
    {
        return $this->jsonResponse($metabaseService->getDashboards());
    }

    #[Route('', name: 'save', methods: ['POST'])]
    public function saveConfig(Request $request)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ConfigureMetabase::getName());

        $url = '/admin/module/Metabase';

        try {
            $vform = $this->validateForm($form);

            Metabase::setConfigValue(Metabase::METABASE_URL_CONFIG_KEY, $vform->get(Metabase::METABASE_URL_CONFIG_KEY)->getData());
            Metabase::setConfigValue(Metabase::METABASE_EMBEDDING_KEY_CONFIG_KEY, $vform->get(Metabase::METABASE_EMBEDDING_KEY_CONFIG_KEY)->getData());
            Metabase::setConfigValue(Metabase::METABASE_USERNAME_CONFIG_KEY, $vform->get(Metabase::METABASE_USERNAME_CONFIG_KEY)->getData());
            Metabase::setConfigValue(Metabase::METABASE_PASSWORD_CONFIG_KEY, $vform->get(Metabase::METABASE_PASSWORD_CONFIG_KEY)->getData());

            // Redirect to the success URL,
            if ('stay' !== $request->get('save_mode')) {
                $url = '/admin/modules';
            }
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans('Metabase update config'),
                $e->getMessage(),
                $form,
                $e
            );
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'config', 'success' => 1]));
    }
}
