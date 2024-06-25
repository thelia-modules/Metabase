<?php

namespace Metabase\Form;

use Metabase\Metabase;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class ImportMetabase extends BaseForm
{
    protected function buildForm(): void
    {
        $translator = Translator::getInstance();
        $this->formBuilder
            ->add(
                'metabaseName',
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'required' => true,
                    'data' => Metabase::getConfigValue(Metabase::METABASE_NAME_CONFIG_KEY),
                    'label' => $translator->trans('Metabase name', [], Metabase::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => Metabase::METABASE_NAME_CONFIG_KEY,
                    ],
                ]
            )
            ->add(
                'dbName',
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'label' => $translator->trans('database name', [], Metabase::DOMAIN_NAME),
                    'required' => true,
                    'data' => Metabase::getConfigValue(Metabase::METABASE_DB_NAME_CONFIG_KEY),
                    'label_attr' => ['for' => Metabase::METABASE_DB_NAME_CONFIG_KEY],
                ]
            )
            ->add(
                'engine',
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'label' => $translator->trans('database engine', [], Metabase::DOMAIN_NAME),
                    'required' => true,
                    'data' => Metabase::getConfigValue(Metabase::METABASE_ENGINE_CONFIG_KEY),
                    'label_attr' => [
                        'for' => Metabase::METABASE_ENGINE_CONFIG_KEY,
                        'help' => 'mysql',
                    ],
                ]
            )
            ->add(
                'host',
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'label' => $translator->trans('database host', [], Metabase::DOMAIN_NAME),
                    'required' => true,
                    'data' => Metabase::getConfigValue(Metabase::METABASE_HOST_CONFIG_KEY),
                    'label_attr' => [
                        'for' => Metabase::METABASE_HOST_CONFIG_KEY,
                        'help' => 'localhost',
                    ],
                ]
            )
            ->add(
                'port',
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'label' => $translator->trans('database port', [], Metabase::DOMAIN_NAME),
                    'required' => true,
                    'data' => Metabase::getConfigValue(Metabase::METABASE_PORT_CONFIG_KEY),
                    'label_attr' => [
                        'for' => Metabase::METABASE_PORT_CONFIG_KEY,
                        'help' => '3306',
                    ],
                ]
            )
            ->add(
                'user',
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'label' => $translator->trans('database user', [], Metabase::DOMAIN_NAME),
                    'required' => true,
                    'data' => Metabase::getConfigValue(Metabase::METABASE_DB_USERNAME_CONFIG_KEY),
                    'label_attr' => [
                        'for' => Metabase::METABASE_DB_USERNAME_CONFIG_KEY,
                    ],
                ]
            )
            ->add(
                'password',
                PasswordType::class,
                [
                    'label' => $translator->trans('database password', [], Metabase::DOMAIN_NAME),
                    'required' => false,
                ]
            )
        ;
    }
}
