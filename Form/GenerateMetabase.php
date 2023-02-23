<?php

namespace Metabase\Form;

use Metabase\Metabase;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use function Sodium\add;

class GenerateMetabase extends BaseForm
{

    protected function buildForm()
    {
        $translator = Translator::getInstance();
        $this->formBuilder
            ->add(
                "order_type",
                TextType::class,
                [
                    'data' => Metabase::getConfigValue(Metabase::CONFIG_METABASE_ORDER_TYPE),
                    'label' => $translator->trans('order_type', [], Metabase::DOMAIN_NAME),
                    'label_attr' => ['for' => Metabase::CONFIG_METABASE_NAME],
                ]
            );
    }

    public static function getName(): string
    {
        return 'generate_metabase_form';
    }
}