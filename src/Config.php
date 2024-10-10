<?php
/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scrawler\Arca;

/**
 * Class to store the configuration.
 */
class Config
{
    public function __construct(
        private bool $isUUID = false,
        private bool $isFrozen = false)
    {
    }

    /**
     * Get if the connection is using UUID.
     */
    public function isUsingUUID(): bool
    {
        return $this->isUUID;
    }

    /**
     * Set if the connection is frozen.
     */
    public function setFrozen(bool $isFrozen): void
    {
        $this->isFrozen = $isFrozen;
    }

    /**
     * Get if the connection is frozen.
     */
    public function isFrozen(): bool
    {
        return $this->isFrozen;
    }
}
