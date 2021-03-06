<?php


namespace EventSauce\EventSourcing;

interface AggregateRootId
{
    /**
     * @return string
     */
    public function toString(): string;

    /**
     * @param string $aggregateRootId
     * @return static
     */
    public static function fromString(string $aggregateRootId): AggregateRootId;
}