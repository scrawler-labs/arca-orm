<?php

it('tests register method', function () {
    $event = new \Scrawler\Arca\Event();
    $dispatch = $event->dispatch('test', ['test']);
    expect($dispatch)->toBeTrue();
});