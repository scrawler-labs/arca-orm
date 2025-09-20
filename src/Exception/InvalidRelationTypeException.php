<?php

/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scrawler\Arca\Exception;

class InvalidRelationTypeException extends \Exception
{
    public function __construct(?string $type)
    {
        parent::__construct('Invalid relation type: ' . $type);
    }
}
