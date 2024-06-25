<?php

namespace Metabase\Form;

use Metabase\Metabase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class SyncingMetabase extends BaseForm
{
    protected function buildForm(): void
    {
        $translator = Translator::getInstance();

        $this->formBuilder
            ->add('syncingOption', ChoiceType::class, [
                'choices' => [
                    $translator->trans('is_full_sync', [], Metabase::DOMAIN_NAME) => 'is_full_sync',
                    $translator->trans('is_on_demand', [], Metabase::DOMAIN_NAME) => 'is_on_demand',
                    $translator->trans('sync_only', [], Metabase::DOMAIN_NAME) => 'sync_only',
                ],
                'data' => Metabase::getConfigValue(Metabase::METABASE_SYNCING_OPTION),
                'label' => $translator->trans('syncing_option', [], Metabase::DOMAIN_NAME),
                'required' => true,
            ])
            ->add('syncingSchedule', ChoiceType::class, [
                'choices' => [
                    $translator->trans('Daily', [], Metabase::DOMAIN_NAME) => 'daily',
                    $translator->trans('Hourly', [], Metabase::DOMAIN_NAME) => 'hourly',
                ],
                'data' => Metabase::getConfigValue(Metabase::METABASE_SYNCING_SCHEDULE),
                'label' => $translator->trans('syncing_schedule', [], Metabase::DOMAIN_NAME),
                'required' => false,
            ])
            ->add('syncingTime', NumberType::class, [
                'data' => Metabase::getConfigValue(Metabase::METABASE_SYNCING_TIME),
                'label' => $this->translator->trans('syncing_time', [], Metabase::DOMAIN_NAME),
                'label_attr' => [
                    'help' => $translator->trans(
                        'Hour of Daily syncing or Minute past the Hourly syncing', [], Metabase::DOMAIN_NAME
                    ),
                ],
                'required' => false,
            ])
            ->add('scanningSchedule', ChoiceType::class, [
                'choices' => [
                    $translator->trans('Daily', [], Metabase::DOMAIN_NAME) => 'daily',
                    $translator->trans('Monthly/Weekly', [], Metabase::DOMAIN_NAME) => 'monthly',
                ],
                'data' => Metabase::getConfigValue(Metabase::METABASE_SCANNING_SCHEDULE),
                'label' => $translator->trans('scanning_schedule', [], Metabase::DOMAIN_NAME),
                'required' => false,
            ])
            ->add('scanningFrame', ChoiceType::class, [
                'choices' => [
                    $translator->trans('First', [], Metabase::DOMAIN_NAME) => 'first',
                    $translator->trans('Last', [], Metabase::DOMAIN_NAME) => 'last',
                    $translator->trans('15th(MidPoint)', [], Metabase::DOMAIN_NAME) => 'mid',
                    $translator->trans('EveryWeek', [], Metabase::DOMAIN_NAME) => null,
                ],
                'data' => Metabase::getConfigValue(Metabase::METABASE_SCANNING_FRAME),
                'label' => $translator->trans('scanning_frame', [], Metabase::DOMAIN_NAME),
                'required' => false,
            ])
            ->add('scanningDay', ChoiceType::class, [
                'choices' => [
                    $translator->trans('Monday', [], Metabase::DOMAIN_NAME) => 'mon',
                    $translator->trans('Tuesday', [], Metabase::DOMAIN_NAME) => 'tue',
                    $translator->trans('Wednesday', [], Metabase::DOMAIN_NAME) => 'wed',
                    $translator->trans('Thursday', [], Metabase::DOMAIN_NAME) => 'thu',
                    $translator->trans('Friday', [], Metabase::DOMAIN_NAME) => 'fri',
                    $translator->trans('Saturday', [], Metabase::DOMAIN_NAME) => 'sat',
                    $translator->trans('Sunday', [], Metabase::DOMAIN_NAME) => 'sun',
                    $translator->trans('Calendar Day', [], Metabase::DOMAIN_NAME) => null,
                ],
                'data' => Metabase::getConfigValue(Metabase::METABASE_SCANNING_DAY),
                'label' => $translator->trans('scanning_day', [], Metabase::DOMAIN_NAME),
                'label_attr' => [
                    'help' => $translator->trans('The Day of Weekly or Monthly Scan', [], Metabase::DOMAIN_NAME),
                ],
                'required' => false,
            ])
            ->add('scanningTime', NumberType::class, [
                'data' => Metabase::getConfigValue(Metabase::METABASE_SCANNING_TIME),
                'label' => $translator->trans('scanning_time', [], Metabase::DOMAIN_NAME),
                'label_attr' => [
                    'help' => $translator->trans('Hour of the Scan', [], Metabase::DOMAIN_NAME),
                ],
                'required' => false,
            ])
            ->add(
                'refingerprint',
                CheckboxType::class,
                [
                    'data' => (bool) Metabase::getConfigValue(Metabase::METABASE_REFINGERPRINT),
                    'label' => $translator->trans('refingerprint', [], Metabase::DOMAIN_NAME),
                    'required' => false,
                ]
            )
        ;
    }
}
