<?php

namespace App\Database;

use PDO;
use PDOStatement;

class QueryLogger
{
    private static array $queries = [];
    private static bool $enabled = true;

    public static function enable(bool $status = true): void
    {
        self::$enabled = $status;
    }

    /**
     * @param PDO $pdo
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public static function execute(PDO $pdo, string $sql, array $params = []): PDOStatement
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            if (empty($params)) {
                $stmt = $pdo->query($sql);
            } else {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            if (self::$enabled) {
                self::logQuery([
                    'sql' => $sql,
                    'params' => $params,
                    'time' => ($endTime - $startTime) * 1000, // ms
                    'memory' => ($endMemory - $startMemory) / 1024, // KB
                    'rows_affected' => $stmt->rowCount(),
                    'success' => true
                ]);
            }

            return $stmt;
        } catch (\PDOException $e) {
            $endTime = microtime(true);

            if (self::$enabled) {
                self::logQuery([
                    'sql' => $sql,
                    'params' => $params,
                    'time' => ($endTime - $startTime) * 1000,
                    'error' => $e->getMessage(),
                    'success' => false
                ]);
            }

            throw $e;
        }
    }

    private static function logQuery(array $info): void
    {
        self::$queries[] = array_merge($info, [
            'timestamp' => date('Y-m-d H:i:s'),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
    }

    public static function getQueries(): array
    {
        return self::$queries;
    }

    public static function clear(): void
    {
        self::$queries = [];
    }

    public static function printStats(): void
    {
        $totalQueries = count(self::$queries);
        $totalTime = array_sum(array_column(self::$queries, 'time'));
        $totalMemory = array_sum(array_column(self::$queries, 'memory'));

        echo "\n" . str_repeat('=', 80) . "\n";
        echo "📊 QUERY STATISTICS\n";
        echo str_repeat('=', 80) . "\n";
        echo sprintf("Total queries: %d\n", $totalQueries);
        echo sprintf("Total time: %.2f ms\n", $totalTime);
        echo sprintf("Average time: %.2f ms\n", $totalQueries > 0 ? $totalTime / $totalQueries : 0);
        echo sprintf("Total memory: %.2f KB\n", $totalMemory);
        echo str_repeat('=', 80) . "\n\n";
    }

    public static function printDetailed(): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "DETAILED QUERY LOG\n";
        echo str_repeat('=', 80) . "\n\n";

        foreach (self::$queries as $index => $query) {
            echo sprintf("[%d] %s\n", $index + 1, $query['timestamp']);
            echo str_repeat('-', 80) . "\n";

            echo "SQL: " . self::formatSql($query['sql']) . "\n";

            if (!empty($query['params'])) {
                echo "Params: " . json_encode($query['params'], JSON_UNESCAPED_UNICODE) . "\n";
            }

            $timeColor = $query['time'] < 10 ? '🟢' : ($query['time'] < 100 ? '🟡' : '🔴');
            echo sprintf("Time: %s %.2f ms\n", $timeColor, $query['time']);

            echo sprintf("Memory: %.2f KB\n", $query['memory']);

            if (isset($query['rows_affected'])) {
                echo sprintf("Rows: %d\n", $query['rows_affected']);
            }

            if (!$query['success']) {
                echo "❌ Error: " . $query['error'] . "\n";
            }

            echo "\n";
        }
    }

    private static function formatSql(string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = trim($sql);

        if (strlen($sql) > 200) {
            $sql = substr($sql, 0, 197) . '...';
        }

        return $sql;
    }

    public static function saveToFile(string $filename): void
    {
        $content = "QUERY LOG - " . date('Y-m-d H:i:s') . "\n";
        $content .= str_repeat('=', 80) . "\n\n";

        foreach (self::$queries as $index => $query) {
            $content .= sprintf("[%d] %s\n", $index + 1, $query['timestamp']);
            $content .= "SQL: {$query['sql']}\n";

            if (!empty($query['params'])) {
                $content .= "Params: " . json_encode($query['params']) . "\n";
            }

            $content .= sprintf("Time: %.2f ms | Memory: %.2f KB\n", $query['time'], $query['memory']);
            $content .= "\n";
        }

        file_put_contents($filename, $content);
        echo "Query log saved to: {$filename}\n";
    }
}