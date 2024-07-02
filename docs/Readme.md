# Metabase Docs for developers

If you want to make your own Datatable, you can use the EventListener :

Here is a basic example of an event Listener
```php
<?php

namespace Metabase\EventListener;

use Metabase\CustomService\MyStatisticService;
use Metabase\Event\MetabaseStatisticEvent;
use Metabase\Event\MetabaseStatisticEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyCustomListener implements EventSubscriberInterface
{
    public function __construct(protected MyStatisticService $statisticService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MetabaseStatisticEvents::ADD_METABASE_STATISTICS => ['addMetabaseStatistics', '128'],
        ];
    }

    public function addMetabaseStatistics(MetabaseStatisticEvent $event): void
    {
        $fields = $event->getFields();
        $collectionRootId = $event->getCollectionRootId();

        // Create a Collection for your new Dashboards, Cards, Questions ...
        $myCollection = $this->statisticService->generateCollection('myCustomCollection', $collectionRootId);
        // Call this method in your new service that will generate the new Dashboards
        $this->statisticService->generateStatisticMetabase($myCollection->id, $fields);
    }
}
```

Now you want to Customize your Cards, Dashboards.

Here is a basic example of a custom service:
```php
<?php

namespace Metabase\CustomService;

use Metabase\Service\Base\AbstractMetabaseService;

class MyStatisticService extends AbstractMetabaseService
{
    public function generateStatisticMetabase(int $collectionId, array $fields): void
    {
        // I recommend you to create an array with all your custom parameters
        $defaultFields = [
            [
                // example for a single date
                'id' => $this->getUuidDate1(),
                'tag' => 'invoiceDate1',
                'date' => $startDate->format('Y-m-d'),

                // example for a relative date
                'id2' => $this->getUuidDate2(),
                'tag2' => 'invoiceDate2',
                'date2' => 'thisyear',
            ],
        ];
        
        // Create a dashboard
        $dashboard = $this->generateDashboardMetabase(
            'dashboard name',
            'dashboard description',
            $collectionId
        );

        // Create a cart
        $card = $this->generateCardMetabase(
            'card name',
            'dashboard description',
            'line',
            $collectionId,
            'Your Sql Query',
            $fields
        );

        // Format your card to fit into the dashboard card
        $dashboardCard = $this->formatDashboardCard(
            $card->id,
            [],
            0,
            0,
            24,
            5,
            $card->id
        );

        // Embed your dashboardCart
        $this->embedDashboard(
            $dashboard->id,
            [
                'invoiceDate1' => 'enabled',
                'invoiceDate2' => 'enabled',
                'orderStatus' => 'enabled',
            ],
            [$dashboardCard]
        );

        // Publish your dashboardCart
        $this->publishDashboard($dashboard->id);
    }

    ...
```

Note: you will have to implement the following methods to create your own Statistic
- `buildVisualizationSettings`
- `buildParameters`
- `buildDatasetQuery`
- `getCardParameterMapping`
- `getDashboardParameters()`

<details>
<summary>Click for an Example of these methods</summary>

```php
<?php
    public function buildVisualizationSettings(): array
    {
        return [
            'graph.dimensions' => [],
            'graph.series_order_dimension' => null,
            'graph.series_order' => null,
            'graph.metrics' => [],
            'column_settings' => [],
            'series_settings' => [],
        ];
    }
    
    public function buildParameters(array $defaultOrderStatus, array $defaultFields = []): array
    {
        return [
            [
                'id' => $defaultFields['id'], // $this->getUuidDate1()
                'type' => 'date/single',
                'target' => ['dimension', ['template-tag', 'invoiceDate1']],
                'name' => $defaultFields['tag'],
                'slug' => $defaultFields['tag'],
                'default' => $defaultFields['date'],
            ],
            [
                'id' => $defaultFields['id2'], // $this->getUuidDate2(),
                'type' => 'date/relative',
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'invoiceDate',
                    ],
                ],
                'name' => $defaultFields['tag2'],
                'slug' => $defaultFields['tag2'],
                'default' => $defaultFields['date'], // 'thisyear' or 'past1years'
            ],
            [
                'id' => $this->getUuidOrderStatus(),
                'type' => 'string/=',
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'orderStatus',
                    ],
                ],
                'name' => 'orderStatus',
                'slug' => 'orderStatus',
                'default' => $defaultOrderStatus,
            ],
        ];
    }
    
    public function buildDatasetQuery(string $query, array $defaultOrderStatus, array $fields, array $defaultFields = []): array
    {
        // example of search from your fields the column in your table
        // Note: don't forget to make a jointure in your sql query if needed
        $fieldDate = $this->metabaseAPIService->searchField($fields, 'invoice_date', 'order');
        $fieldOrderStatus = $this->metabaseAPIService->searchField($fields, 'title', 'order_status_i18n');

        return [
            'database' => (int) Metabase::getConfigValue(Metabase::METABASE_DB_ID_CONFIG_KEY), // mandatory
            'native' => [
                'template-tags' => [
                    'invoiceDate1' => [
                        'id' => $defaultFields['id'], // $this->getUuidDate1(),
                        'name' => $defaultFields['tag'],
                        'display-name' => $defaultFields['tag'],
                        'type' => 'date',
                        'widget-type' => 'date/single',
                        'default' => $defaultFields['date'],
                        'required' => true,
                    ],
                    'invoiceDate2' => [
                        'id' => $defaultFields['id2'], // $this->getUuidDate2(),
                        'name' => $defaultFields['tag2'],
                        'display-name' => $defaultFields['tag2'],
                        'type' => 'dimension',
                        'dimension' => [
                            'field',
                            $fieldDate,
                            null,
                        ],
                        'widget-type' => 'date/relative',
                        'default' => $defaultFields['date2'], // 'thisyear' or 'past1years'
                        'required' => true,
                    ],
                    'orderStatus' => [
                        'id' => $this->getUuidOrderStatus(),
                        'name' => 'orderStatus',
                        'display-name' => 'OrderStatus',
                        'type' => 'dimension',
                        'dimension' => [
                            'field',
                            $fieldOrderStatus,
                            null,
                        ],
                        'widget-type' => 'string/=',
                        'default' => $defaultOrderStatus,
                    ],
                ],
                'query' => $query, // Your SQL Query
            ],
            'type' => 'native',
        ];
    }
    
    public function getCardParameterMapping(int ...$cardsId): array
    {
        // Map your parameters with your cards
        return [
            [
                'parameter_id' => $this->getUuidParamDate1(),
                'card_id' => $cardsId[0],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'invoiceDate1',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->getUuidParamDate2(),
                'card_id' => $cardsId[0],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'invoiceDate2',
                    ],
                ],
            ],
            [
                'parameter_id' => $this->getUuidParamOrderStatus(),
                'card_id' => $cardsId[0],
                'target' => [
                    'dimension',
                    [
                        'template-tag',
                        'orderStatus',
                    ],
                ],
            ],
        ];
    }
    
    public function getDashboardParameters(array $defaultFields): array
    {
        return [
            [
                'name' => 'invoiceDate1',
                'slug' => 'invoiceDate1',
                'id' => $this->getUuidParamDate1(),
                'type' => 'date/single',
                'sectionId' => 'date',
                'default' => $defaultFields['date1'],
            ],
            [
                'name' => 'invoiceDate2',
                'slug' => 'invoiceDate2',
                'id' => $this->getUuidParamDate2(),
                'type' => 'date/relative',
                'sectionId' => 'date',
                'default' => 'thisyear',
            ],
            [
                'name' => 'orderStatus',
                'slug' => 'orderStatus',
                'id' => $this->getUuidParamOrderStatus(),
                'type' => 'string/=',
                'sectionId' => 'string',
                'default' => $this->getDefaultOrderStatus(),
                'values_query_type' => 'list',
                'values_source_config' => [
                    'values' => $this->getValuesSourceConfigValuesOrderStatus(),
                ],
                'values_source_type' => 'static-list',
            ],
        ];
    }
}
```
</details>

[API Post Card](https://www.metabase.com/docs/latest/api/card#post-apicard)
- `buildVisualizationSettings` => visualization_settings, 
- `buildParameters` => parameters, 
- `buildDatasetQuery` => dataset_query

if you don't want to use the `generateCardMetabase()` method you can use `generateCustomCardMetabase()` instead.

Please check [Metabase API Documentation](https://www.metabase.com/docs/latest/api-documentation)
for more information