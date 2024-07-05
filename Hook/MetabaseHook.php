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
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\LangQuery;

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
        $metabaseKey = Metabase::getConfigValue(Metabase::METABASE_EMBEDDING_KEY_CONFIG_KEY);

        if (null === $locale = $event->getTemplateVars()['locale']) {
            $locale = LangQuery::create()->findOneByByDefault(1)?->getLocale();
        }

        $apiDashboards = [];

        $dashboards = [];
        $dashboardsName = [];
        $errorMessage = null;
        $countDisable = $this->countDisableDatatable();

        $metabase = new \Metabase\Embed($metabaseUrl, $metabaseKey, false, '100%', '600');

        try {
            $apiCollections = $this->metabaseAPIService->getCollectionsItems(
                Metabase::getConfigValue(Metabase::METABASE_COLLECTION_ROOT_ID_CONFIG_KEY.'_'.$locale),
                'collection'
            )['data'];

            // F*** Metabase API that don't send collection order by id
            usort($apiCollections, static function ($a, $b) {
                return $a['id'] - $b['id'];
            });

            foreach ($apiCollections as $key => $apiCollection) {
                $apiDashboards[$key] = $this->metabaseAPIService->getCollectionsItems($apiCollection['id'], 'dashboard')['data'];
            }

            $tableId = 0;
            foreach ($apiDashboards as $key => $apiDashboard) {
                $dashboardsName[$tableId] = $apiCollections[$key]['name'];

                foreach ($apiDashboard as $key2 => $dashboard) {
                    $dashboards[] = $metabase->dashboardIFrame($dashboard['id']);
                    if ($key2 > 0) {
                        ++$tableId;
                    }
                }
                ++$tableId;
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
                    'countDisable' => $countDisable,
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

    private function countDisableDatatable(): bool
    {
        $countDisable = 0;
        if (!Metabase::getConfigValue(Metabase::METABASE_DISABLE_BRAND_CONFIG_KEY)) {
            ++$countDisable;
        }

        if (!Metabase::getConfigValue(Metabase::METABASE_DISABLE_CATEGORY_CONFIG_KEY)) {
            ++$countDisable;
        }

        if (!Metabase::getConfigValue(Metabase::METABASE_DISABLE_CATEGORY_CONFIG_KEY)) {
            ++$countDisable;
        }

        return $countDisable;
    }
}
