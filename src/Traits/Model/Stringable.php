<?php

namespace Scrawler\Arca\Traits\Model;

trait Stringable
{
    /**
     * Converts model into json object
     * @return string
     */

    public function toString(): string
    {
        return \Safe\json_encode($this->toArray());
    }

    /**
     * Converts model into json object
     * @return string
     */

    public function __toString(): string
    {
        return $this->toString();
    }
}