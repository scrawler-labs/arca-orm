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

use Scrawler\Arca\Connection;
use Scrawler\Arca\Model;

/**
 * Class for initializing and managing models.
 */
class ModelManager
{
    /**
     * Store the instance of current connection.
     */
    private Connection $connection;

    /**
     * Create a new model.
     */
    public function create(string $name): Model
    {
        return new Model($name, $this->connection);
    }

    /**
     * Set the connection.
     */
    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }
}
