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

use Scrawler\Arca\Model;

/**
 * Setter trait to provide setter methods to the model.
 */
trait Setter
{
    /**
     * Set all properties of model via array.
     *
     * @param array<mixed> $properties
     */
    public function setProperties(array $properties): Model
    {
        foreach ($properties as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Set all properties of model loaded via database.
     *
     * @param array<mixed> $properties
     */
    public function setLoadedProperties(array $properties): Model
    {
        $this->__properties['all'] = $properties;
        $this->__properties['self'] = $properties;
        foreach ($properties as $key => $value) {
            $this->__properties['type'][$key] = $this->connection->getTableManager()->getTable($this->table)->getColumn($key)->getComment();
        }
        $this->__meta['id'] = $properties['id'];

        return $this;
    }

    /**
     * call when model is loaded from database.
     */
    public function setLoaded(): Model
    {
        $this->__meta['is_loaded'] = true;
        $this->__meta['id_error'] = false;

        return $this;
    }
}
