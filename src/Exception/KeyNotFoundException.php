<?php
namespace Scrawler\Arca\Exception;

class KeyNotFoundException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Key you are trying to access does not exist");
    }
}
