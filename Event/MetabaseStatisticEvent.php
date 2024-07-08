<?php

namespace Metabase\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MetabaseStatisticEvent extends Event
{
    private array $fields;
    private int $collectionRootId;
    private string $locale;

    public function __construct(int $collectionRootId, array $fields, string $locale)
    {
        $this->collectionRootId = $collectionRootId;
        $this->fields = $fields;
        $this->locale = $locale;
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

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): MetabaseStatisticEvent
    {
        $this->locale = $locale;

        return $this;
    }
}
