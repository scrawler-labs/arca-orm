<?php

namespace Scrawler\Arca\Traits\Model;

trait ArrayAccess
{
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            throw new \Exception('Offset cannot be null');
        } else {
            $this->set($offset, $value);
        }
    }

    public function offsetExists($offset): bool
    {
        return $this->isset($offset);
    }

    public function offsetUnset($offset): void
    {
        $this->unset($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }
}
