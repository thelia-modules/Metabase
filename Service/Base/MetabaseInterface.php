<?php

namespace Metabase\Service\Base;

interface MetabaseInterface
{
    public function generateStatisticMetabase(int $collectionId, array $fields): void;

    public function buildVisualizationSettings(): array;

    public function buildParameters(array $defaultOrderStatus, array $defaultFields = []): array;

    public function buildDatasetQuery(string $query, array $defaultOrderStatus, array $fields, array $defaultFields = []): array;

    public function getCardParameterMapping(int ...$cardsId): array;

    public function getDashboardParameters(array $defaultFields): array;
}
