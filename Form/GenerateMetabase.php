<?php

namespace Metabase\Form;

use Metabase\Metabase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class GenerateMetabase extends BaseForm
{
    protected function buildForm(): void
    {
        $translator = Translator::getInstance();
        $this->formBuilder
            ->add(
                'order_type',
                TextType::class,
                [
                    'data' => Metabase::getConfigValue(Metabase::METABASE_ORDER_TYPE_CONFIG_KEY),
                    'label' => $translator->trans('order_type', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::METABASE_ORDER_TYPE_CONFIG_KEY],
                ]
            )
            ->add(
                'disable_brand',
                CheckboxType::class,
                [
                    'required' => false,
                    'data' => (bool) Metabase::getConfigValue(Metabase::METABASE_DISABLE_BRAND_CONFIG_KEY),
                    'label' => $translator->trans('disable_brand', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::METABASE_DISABLE_BRAND_CONFIG_KEY],
                ]
            )
            ->add(
                'disable_category',
                CheckboxType::class,
                [
                    'required' => false,
                    'data' => (bool) Metabase::getConfigValue(Metabase::METABASE_DISABLE_CATEGORY_CONFIG_KEY),
                    'label' => $translator->trans('disable_category', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::METABASE_DISABLE_CATEGORY_CONFIG_KEY],
                ]
            )
            ->add(
                'disable_product',
                CheckboxType::class,
                [
                    'required' => false,
                    'data' => (bool) Metabase::getConfigValue(Metabase::METABASE_DISABLE_PRODUCT_CONFIG_KEY),
                    'label' => $translator->trans('disable_product', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::METABASE_DISABLE_PRODUCT_CONFIG_KEY],
                ]
            )
        ;
    }
}
