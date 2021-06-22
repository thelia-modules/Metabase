<?php

namespace Metabase\Controller;

use Metabase\Form\ConfigureMetabase;
use Metabase\Metabase;
use Metabase\Service\MetabaseService;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;

class ConfigurationController extends AdminController
{
    public function renderConfigPageAction()
    {
        return $this->render('module-configure', ['module_code' => 'Metabase']);
    }

    public function getDashboards(MetabaseService $metabaseService)
    {
        return $this->jsonResponse($metabaseService->getDashboards());
    }

    /**
     * @Route("/admin/module/metabase/configure", name="metabase.configuration.save", methods="post")
     */
    public function saveConfig(Request $request)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ConfigureMetabase::getName());

        $url = '/admin/module/Metabase';

        try {
            $vform = $this->validateForm($form);

            Metabase::setConfigValue(Metabase::CONFIG_KEY_URL, $vform->get(Metabase::CONFIG_KEY_URL)->getData());
            Metabase::setConfigValue(Metabase::CONFIG_KEY_TOKEN, $vform->get(Metabase::CONFIG_KEY_TOKEN)->getData());
            Metabase::setConfigValue(Metabase::CONFIG_USERNAME, $vform->get(Metabase::CONFIG_USERNAME)->getData());
            Metabase::setConfigValue(Metabase::CONFIG_PASS, $vform->get(Metabase::CONFIG_PASS)->getData());

            // Redirect to the success URL,
            if ('stay' !== $request->get('save_mode')) {
                $url = '/admin/modules';
            }
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans('Metabase update config'),
                $message = $e->getMessage(),
                $form,
                $e
            );
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url, ['tab' => 'config', 'success' => 1]));
    }
}
