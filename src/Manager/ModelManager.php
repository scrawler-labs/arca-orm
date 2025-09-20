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

use DI\Container;
use Scrawler\Arca\Config;
use Scrawler\Arca\Model;

/**
 * Class for initializing and managing models.
 */
final class ModelManager
{
    public function __construct(
        private readonly Container $container,
        private readonly Config $config,
    ) {
    }

    /**
     * Create a new model instance.
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function create(string $name): Model
    {
        // Try to load specific model class first if modelNamespace is not empty
        $namespace = $this->config->getModelNamespace();
        if (!empty($namespace)) {
            $modelClass = $namespace.ucfirst($name);
            if (class_exists($modelClass)) {
                return $this->container->make($modelClass);
            }
        }

        // Fallback to generic model with table name
        return $this->container->make(Model::class, ['table' => $name]);
    }
}
