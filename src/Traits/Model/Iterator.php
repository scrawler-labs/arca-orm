<?php

namespace Scrawler\Arca\Traits\Model;
use Scrawler\Arca\Model;
use Scrawler\Arca\Collection;

trait Iterator{
     /**
     * Get all properties in array form
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $props = $this->getProperties();
        foreach ($props as $key => $value) {
            if ($value instanceof Model) {
                $props[$key] = $value->toArray();
            }
            if ($value instanceof Collection) {
                $props[$key] = $value->toArray();
            }
        }
        return $props;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->toArray());
    }
}