<?php

namespace Metabase\Form;

use Metabase\Metabase;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class ConfigureMetabase extends BaseForm
{
    protected function buildForm()
    {
        $translator = Translator::getInstance();
        $this->formBuilder
            ->add(
                Metabase::CONFIG_KEY_URL,
                UrlType::class,
                [
                    'constraints' => [new NotBlank()],
                    'data' => Metabase::getConfigValue(Metabase::CONFIG_KEY_URL),
                    'label' => $translator->trans('Metabase url', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::CONFIG_KEY_URL],
                ]
            )
            ->add(
                Metabase::CONFIG_KEY_TOKEN,
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'required' => true,
                    'data' => Metabase::getConfigValue(Metabase::CONFIG_KEY_TOKEN),
                    'label' => $translator->trans('Metabase token', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::CONFIG_KEY_TOKEN],
                ]
            )
            ->add(
                Metabase::CONFIG_USERNAME,
                EmailType::class,
                [
                    'constraints' => [new NotBlank()],
                    'data' => Metabase::getConfigValue(Metabase::CONFIG_USERNAME),
                    'label' => $translator->trans('Metabase username (mail)', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::CONFIG_USERNAME],
                ]
            )
            ->add(
                Metabase::CONFIG_PASS,
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'data' => Metabase::getConfigValue(Metabase::CONFIG_PASS),
                    'label' => $translator->trans('Metabase password', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::CONFIG_PASS],
                ]
            )
        ;
    }

    public static function getName(): string
    {
        return 'configure_metabase_form';
    }
}
