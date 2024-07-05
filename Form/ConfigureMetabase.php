<?php

namespace Metabase\Form;

use Metabase\Metabase;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class ConfigureMetabase extends BaseForm
{
    protected function buildForm(): void
    {
        $translator = Translator::getInstance();
        $this->formBuilder
            ->add(
                Metabase::METABASE_URL_CONFIG_KEY,
                UrlType::class,
                [
                    'constraints' => [new NotBlank()],
                    'data' => Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY),
                    'label' => $translator->trans('Metabase url', [], Metabase::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => Metabase::METABASE_URL_CONFIG_KEY,
                        'help' => $translator->trans('example : http://localhost:3000', [], Metabase::DOMAIN_NAME),
                    ],
                ]
            )
            ->add(
                Metabase::METABASE_EMBEDDING_KEY_CONFIG_KEY,
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'required' => true,
                    'data' => Metabase::getConfigValue(Metabase::METABASE_EMBEDDING_KEY_CONFIG_KEY),
                    'label' => $translator->trans('Metabase token (integration token)', [], Metabase::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => Metabase::METABASE_EMBEDDING_KEY_CONFIG_KEY,
                        'help' => $translator->trans('Activate Embedding here : https://{your_metabase}/admin/settings/embedding-in-other-applications', [], Metabase::DOMAIN_NAME),
                    ],
                ]
            )
            ->add(
                Metabase::METABASE_USERNAME_CONFIG_KEY,
                EmailType::class,
                [
                    'constraints' => [new NotBlank()],
                    'data' => Metabase::getConfigValue(Metabase::METABASE_USERNAME_CONFIG_KEY),
                    'label' => $translator->trans('Metabase username (mail)', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::METABASE_USERNAME_CONFIG_KEY],
                ]
            )
            ->add(
                Metabase::METABASE_PASSWORD_CONFIG_KEY,
                PasswordType::class,
                [
                    'constraints' => [new NotBlank()],
                    'data' => Metabase::getConfigValue(Metabase::METABASE_PASSWORD_CONFIG_KEY),
                    'label' => $translator->trans('Metabase password', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::METABASE_PASSWORD_CONFIG_KEY],
                ]
            )
        ;
    }
}
