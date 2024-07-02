<?php

namespace Metabase\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MetabaseStatisticEvent extends Event
{
    private array $fields;
    private int $collectionRootId;

    public function __construct(array $fields, int $collectionRootId)
    {
        $this->fields = $fields;
        $this->collectionRootId = $collectionRootId;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): MetabaseStatisticEvent
    {
        $this->fields = $fields;

        return $this;
    }

    public function getCollectionRootId(): int
    {
        return $this->collectionRootId;
    }

    public function setCollectionRootId(int $collectionRootId): MetabaseStatisticEvent
    {
        $this->collectionRootId = $collectionRootId;

        return $this;
    }
}
