<?php

namespace Metabase\Form;

use Metabase\Metabase;
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
        ;
    }
}
