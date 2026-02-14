<?php

namespace App\Helpers;

class Timer
{
    private static array $timers = [];

    public static function start(string $name): void
    {
        self::$timers[$name] = [
            'start' => microtime(true),
            'start_memory' => memory_get_usage()
        ];
    }

    public static function stop(string $name): void
    {
        if (!isset(self::$timers[$name])) {
            echo "Timer '{$name}' not found\n";
            return;
        }

        $elapsed = (microtime(true) - self::$timers[$name]['start']) * 1000;
        $memoryUsed = (memory_get_usage() - self::$timers[$name]['start_memory']) / 1024;

        echo sprintf(
            "⏱️  [%s] Time: %.2f ms | Memory: %.2f KB\n",
            $name,
            $elapsed,
            $memoryUsed
        );

        unset(self::$timers[$name]);
    }

    public static function measure(string $name, callable $callback)
    {
        self::start($name);
        $result = $callback();
        self::stop($name);
        return $result;
    }
}