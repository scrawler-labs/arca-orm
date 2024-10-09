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

/**
 * Getter trait to provide getter methods to the model.
 */
trait Getter
{
    /**
     * Get current model Id or UUID.
     */
    public function getId(): mixed
    {
        return $this->__meta['id'];
    }

    /**
     * Get current table name of model.
     */
    public function getName(): string
    {
        return $this->table;
    }

    /**
     * Get all properties with relational models in array form.
     *
     * @return array<mixed>
     */
    public function getProperties(): array
    {
        return $this->__properties['all'];
    }

    /**
     * Get self properties without relations in array form.
     *
     * @return array<mixed>
     */
    public function getSelfProperties(): array
    {
        return $this->__properties['self'];
    }

    /**
     * Get all property types in array form.
     *
     * @return array<mixed>
     */
    public function getTypes(): array
    {
        return $this->__properties['type'];
    }

    /**
     * returns all relational models.
     */
    public function getForeignModels(string $type): Collection
    {
        return is_null($this->__meta['foreign_models'][$type]) ? Collection::fromIterable([]) : $this->__meta['foreign_models'][$type];
    }
}
