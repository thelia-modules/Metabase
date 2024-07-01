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
        $metabaseUrl = Metabase::getConfigValue(Metabase::METABASE_URL_CONFIG_KEY);

        $dashboards = [];
        $dashboardsName = [];
        $errorMessage = null;

        $metabase = new MetabaseEmbed($metabaseUrl, false, '100%', '800');

        try {
            $apiDashboards = $this->metabaseAPIService->getPublicDashboards();
            $apiCollections = $this->metabaseAPIService->getCollectionsItems(
                Metabase::getConfigValue(Metabase::METABASE_COLLECTION_ROOT_ID_CONFIG_KEY)
            );

            // F*** Metabase API that don't send collection order by id
            usort($apiCollections['data'], static function ($a, $b) {
                return $a['id'] - $b['id'];
            });

            $tableId = 0;
            foreach ($apiDashboards as $key => $apiDashboard) {
                $dashboards[] = $metabase->dashboardIFrame($apiDashboard['public_uuid']);
                if (4 !== $key && 6 !== $key && 8 !== $key) {
                    $dashboardsName[$key] = $apiCollections['data'][$tableId]['name'];
                    ++$tableId;
                }
            }
        } catch (MetabaseException $exception) {
            $errorMessage = $exception->getMessage();
        }

        $event->add(
            $this->render(
                'metabase-module.html',
                [
                    'dashboards' => $dashboards,
                    'dashboardsName' => $dashboardsName,
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
