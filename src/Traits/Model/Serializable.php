<?php

namespace Scrawler\Arca\Traits\Model;

trait Serializable{

    /**
     * @return array<mixed>
     */
    public function __serialize(): array{
        return $this->getProperties();
    }

    /**
     * @param array<mixed> $data
     */
    public function __unserialize(array $data): void{
        $this->setProperties($data);
    }

}