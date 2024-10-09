<?php
/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scrawler\Arca\Traits\Model;

/**
 * Stringable trait to provide string conversion methods to the model.
 */
trait Stringable
{
    /**
     * Converts model into json object.
     */
    public function toString(): string
    {
        return \Safe\json_encode($this->toArray());
    }

    /**
     * Converts model into json object.
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
