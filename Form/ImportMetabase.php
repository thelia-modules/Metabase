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
        protected function buildForm()
        {
            $translator = Translator::getInstance();
            $this->formBuilder
                ->add(
                    "metabaseName",
                    TextType::class,
                    [
                        'constraints' => [new NotBlank()],
                        'required' => true,
                        'data' => Metabase::getConfigValue(Metabase::CONFIG_METABASE_NAME),
                        'label' => $translator->trans('Metabase name', [], Metabase::DOMAIN_NAME),
                        'label_attr' => ['for' => Metabase::CONFIG_METABASE_NAME],
                    ]
                )
                ->add(
                    "dbName",
                    TextType::class,
                    [
                        'constraints' => [new NotBlank()],
                        'label' => $translator->trans('database name', [], Metabase::DOMAIN_NAME),
                        'required' => true,
                        'data' => Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_NAME),
                        'label_attr' => ['for' => Metabase::CONFIG_METABASE_DB_NAME],
                    ]
                )
                ->add(
                    "engine",
                    TextType::class,
                    [
                        'constraints' => [new NotBlank()],
                        'label' => $translator->trans('database engine', [], Metabase::DOMAIN_NAME),
                        'required' => true,
                        'data' => Metabase::getConfigValue(Metabase::CONFIG_METABASE_ENGINE),
                        'label_attr' => ['for' => Metabase::CONFIG_METABASE_ENGINE],
                    ]
                )
                ->add(
                    "host",
                    TextType::class,
                    [
                        'constraints' => [new NotBlank()],
                        'label' => $translator->trans('database host', [], Metabase::DOMAIN_NAME),
                        'required' => true,
                        'data' => Metabase::getConfigValue(Metabase::CONFIG_METABASE_HOST),
                        'label_attr' => ['for' => Metabase::CONFIG_METABASE_HOST],
                    ]
                )
                ->add(
                    "port",
                    TextType::class,
                    [
                        'constraints' => [new NotBlank()],
                        'label' => $translator->trans('database port', [], Metabase::DOMAIN_NAME),
                        'required' => true,
                        'data' => Metabase::getConfigValue(Metabase::CONFIG_METABASE_PORT),
                        'label_attr' => ['for' => Metabase::CONFIG_METABASE_PORT],
                    ]
                )
                ->add(
                    "user",
                    TextType::class,
                    [
                        'constraints' => [new NotBlank()],
                        'label' => $translator->trans('database user', [], Metabase::DOMAIN_NAME),
                        'required'   => true,
                        'data' => Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_USERNAME),
                        'label_attr' => ['for' => Metabase::CONFIG_METABASE_DB_USERNAME],
                    ]
                )
                ->add(
                    "password",
                    PasswordType::class,
                    [
                        'label' => $translator->trans('database password', [], Metabase::DOMAIN_NAME),
                        'required'   => false,
                        'data' => Metabase::getConfigValue(Metabase::CONFIG_METABASE_DB_USERNAME),
                        'label_attr' => ['for' => Metabase::CONFIG_METABASE_DB_USERNAME],
                    ]
                );
        }

        public static function getName(): string
        {
            return 'import_metabase_form';
        }
    }