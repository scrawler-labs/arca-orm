<?php
namespace Scrawler\Arca\Exception;

class InvalidModelException extends \Exception
{
    public function __construct()
    {
        parent::__construct("parameter passed to shared list or own list should be array of class \Arca\Model");
    }
}
