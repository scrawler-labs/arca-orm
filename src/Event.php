<?php
declare(strict_types=1);

namespace Scrawler\Arca;


/**
 * A simple event system for any PHP Project
 */
class Event
{
    static $events;

    /**
     * Register an event before it becoming available for use
     */
    private static function register(string $eventname): void
    {
        if (!isset(self::$events[$eventname])) {
            self::$events[$eventname] = array();
        }
    }

    /**
     * Subscribe to a defined event.
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
     */
    public static function dispatch($eventname, $params) : mixed
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
     *
     */
    private static function isEvent($eventname)
    {
        if (!isset(self::$events[$eventname]) || !is_array(self::$events[$eventname])) {
            return false;
        }
        return true;
    }
}

