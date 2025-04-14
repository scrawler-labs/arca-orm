<?php

/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Scrawler\Arca\Manager;

use Scrawler\Arca\Model;

/**
 * Class for initializing and managing models.
 */
class ModelManager
{
    public function __construct(private readonly \DI\Container $container)
    {
    }

    /**
     * Create a new model.
     */
    public function create(string $name): Model
    {
        return $this->container->make(Model::class, ['table' => $name]);
    }
}
