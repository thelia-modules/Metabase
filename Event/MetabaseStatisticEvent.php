<?php

namespace Metabase\Event;

use Metabase\Service\API\MetabaseAPIService;
use Symfony\Contracts\EventDispatcher\Event;

class MetabaseStatisticEvent extends Event
{
    private array $fields;

    public function __construct(protected MetabaseAPIService $metabaseAPIService, array $fields)
    {
        $this->fields = $fields;
    }

    public function getFields(): array
    {
        if (empty($this->fields)) {
            $this->fields = $this->metabaseAPIService->getAllField();
        }

        return $this->fields;
    }

    public function setFields(array $fields): MetabaseStatisticEvent
    {
        $this->fields = $fields;

        return $this;
    }
}
