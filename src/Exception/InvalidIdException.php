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

class InvalidIdException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Force setting of id for model is not allowed');
    }
}
