<?php
declare(strict_types=1);

namespace Scrawler\Arca;


/**
 * A simple event system for any PHP Project
 */
class Event
{
    /**
     * Store all events
     * @var array<string,mixed>
     */
    static array $events;

    /**
     * Register an event before it becoming available for use
     * @param string $eventname
     * @return void
     */
    private static function register(string $eventname): void
    {
        if (!isset(self::$events[$eventname])) {
            self::$events[$eventname] = array();
        }
    }

    /**
     * Subscribe to a defined event.
     * @param string $eventname
     * @param callable $callback
     * @param int $priority
     */
    public static function subscribeTo(string $eventname, callable $callback, int $priority = 0): void
    {
        if (!self::isEvent($eventname)) {
            self::register($eventname);
        }
        self::$events[$eventname][$priority][] = $callback;
    }

    /**
     * Trigger an event, and call all subscribers, giving an array of params.
     * @param string $eventname
     * @param array<mixed> $params
     */
    public static function dispatch(string $eventname, array $params) : mixed
    {
        if (!self::isEvent($eventname)) {
            self::register($eventname);
        }
        foreach (self::$events[$eventname] as $key => $weight) {
            foreach ($weight as $callback) {
                 call_user_func_array($callback, $params);
            }
        }

        return true;
    }

    /**
     * Check that an event is valid before interacting with it.
     * @param string $eventname
     * @return bool
     */
    private static function isEvent(string $eventname): bool
    {
        if (!isset(self::$events[$eventname]) || !is_array(self::$events[$eventname])) {
            return false;
        }
        return true;
    }
}

