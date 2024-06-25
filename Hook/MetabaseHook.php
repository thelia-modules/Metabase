<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Metabase\Hook;

use Metabase\Exception\MetabaseException;
use Metabase\Metabase;
use Metabase\Service\API\MetabaseAPIService;
use Metabase\Service\Embed\MetabaseEmbed;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Translation\Translator;

class MetabaseHook extends BaseHook
{
    public function __construct(protected MetabaseAPIService $metabaseAPIService)
    {
        parent::__construct();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function metabaseHome(HookRenderEvent $event): void
    {
        $translator = Translator::getInstance();
        $metabaseUrl = Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY);

        $dashboards = [];
        $dashboardsName = [];
        $errorMessage = null;

        $metabase = new MetabaseEmbed($metabaseUrl, false, '100%', '600');

        try {
            $apiResult = $this->metabaseAPIService->getPublicDashboards();

            foreach ($apiResult as $iValue) {
                $dashboards[$iValue['id']] = $metabase->dashboardIFrame($iValue['public_uuid']);
                $dashboardsName[$iValue['id']] = $iValue['name'];
            }

        } catch (MetabaseException $exception) {
            $errorMessage = $exception->getMessage();
        }

        ksort($dashboards);
        ksort($dashboardsName);

        $event->add(
            $this->render(
                'metabase-module.html',
                [
                    'dashboards' => array_values($dashboards),
                    'dashboardsName' => array_values($dashboardsName),
                    'othersStatistics' => $translator->trans('All Statistics', [], Metabase::DOMAIN_NAME),
                    'errorMessage' => $errorMessage,
                ]
            )
        );
    }

    public function metabaseConfig(HookRenderEvent $event): void
    {
        $event->add($this->render('module-configuration.html'));
    }

    public function metabaseHomeJs(HookRenderEvent $event): void
    {
        $event->add($this->render(
            'metabase-module-js.html'
        ));
    }
}
