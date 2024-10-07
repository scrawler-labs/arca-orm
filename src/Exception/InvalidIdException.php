<?php
namespace Scrawler\Arca\Exception;

class InvalidIdException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Force setting of id for model is not allowed");
    }
}
