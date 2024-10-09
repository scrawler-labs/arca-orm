<?php
use \Scrawler\Arca\Event;
covers(\Scrawler\Arca\Event::class);

it('tests register method', function () {
    $rand = rand(1, 100);
    $dispatch = Event::dispatch('test.'.$rand, ['test']);
    expect($dispatch)->toBeTrue();
    Event::subscribeTo('test.'.$rand, function ($test) {
        expect($test)->toBe('test');
    });
});