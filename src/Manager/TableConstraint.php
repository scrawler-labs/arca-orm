<?php

/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scrawler\Arca\Manager;

/**
 * Class to store table constraints.
 */
class TableConstraint
{
    public function __construct(
        private readonly string $foreignTableName,
        private readonly string $localColumnName,
        private readonly string $foreignColumnName,
    ) {
    }

    public function getForeignTableName(): string
    {
        return $this->foreignTableName;
    }

    public function getLocalColumnName(): string
    {
        return $this->localColumnName;
    }

    public function getForeignColumnName(): string
    {
        return $this->foreignColumnName;
    }
}
