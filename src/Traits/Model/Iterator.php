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

use Scrawler\Arca\Collection;
use Scrawler\Arca\Model;

/**
 * Iterator trait to provide iterator methods to the model.
 */
trait Iterator
{
    /**
     * Get all properties in array form.
     *
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
