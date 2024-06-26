<?php

namespace Metabase\Controller;

use Metabase\Form\ConfigureMetabase;
use Metabase\Metabase;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;

#[Route('/admin/module/Metabase/configure', name: 'admin_metabase_configure_')]
class ConfigurationController extends AdminController
{
    #[Route('', name: 'save', methods: ['POST'])]
    public function saveConfig()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['Metabase'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ConfigureMetabase::getName());

        try {
            $vform = $this->validateForm($form);

            Metabase::setConfigValue(Metabase::METABASE_URL_CONFIG_KEY, $vform->get(Metabase::METABASE_URL_CONFIG_KEY)->getData());
            Metabase::setConfigValue(Metabase::METABASE_EMBEDDING_KEY_CONFIG_KEY, $vform->get(Metabase::METABASE_EMBEDDING_KEY_CONFIG_KEY)->getData());
            Metabase::setConfigValue(Metabase::METABASE_USERNAME_CONFIG_KEY, $vform->get(Metabase::METABASE_USERNAME_CONFIG_KEY)->getData());
            Metabase::setConfigValue(Metabase::METABASE_PASSWORD_CONFIG_KEY, $vform->get(Metabase::METABASE_PASSWORD_CONFIG_KEY)->getData());

            return $this->generateSuccessRedirect($form);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans('Metabase update config'),
                $e->getMessage(),
                $form,
                $e
            );
        }

        return $this->generateErrorRedirect($form);
    }
}
