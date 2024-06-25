# Metabase Docs for developers

If you want to Contribute this module and add more Statistics Panel

Here is a basic example of a custom service:
```php
class MyStatisticService extends AbstractMetabaseService

    public function generateStatisticMetabase(int $collectionId, array $fields): void
    {
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
        }
        
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
        
        // Create a dashboardCart
        $this->generateDashboardCard($dashboard->id, [$dashboardCard]);

        // Embed your dashboardCart
        $this->embedDashboard($dashboard->id);

        // Publish your dashboardCart
        $this->publishDashboard($dashboard->id);
    }

    ...
}
```

Note: you will have to implement the following methods to create your own Statistic
- `buildVisualizationSettings`
- `buildParameters` 
- `buildDatasetQuery`
- `getCardParameterMapping`
- `getDashboardParameters()`

[API Post Card](https://www.metabase.com/docs/latest/api/card#post-apicard)
- `buildVisualizationSettings` => visualization_settings, 
- `buildParameters` => parameters, 
- `buildDatasetQuery` => dataset_query

if you don't want to use the `generateCardMetabase()` method you can use `generateCustomCardMetabase()` instead.

Please check [Metabase API Documentation](https://www.metabase.com/docs/latest/api-documentation)
for more information