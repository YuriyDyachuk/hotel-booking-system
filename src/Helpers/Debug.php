<?php

namespace App\Helpers;

class Debug
{
    public static function dump($data, string $label = ''): void
    {
        if ($label) {
            echo "\n {$label}\n";
            echo str_repeat('-', 80) . "\n";
        }

        print_r($data);
        echo "\n";
    }

    public static function dd($data, string $label = ''): void
    {
        self::dump($data, $label);
        exit(1);
    }

    public static function explain(\PDO $pdo, string $sql, array $params = []): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo " EXPLAIN ANALYSIS\n";
        echo str_repeat('=', 80) . "\n";
        echo "SQL: {$sql}\n";

        if (!empty($params)) {
            echo "Params: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n";
        }

        echo str_repeat('-', 80) . "\n\n";

        $explainSql = "EXPLAIN " . $sql;
        $stmt = $pdo->prepare($explainSql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($results)) {
            $headers = array_keys($results[0]);

            foreach ($headers as $header) {
                echo str_pad($header, 15) . " | ";
            }
            echo "\n" . str_repeat('-', 80) . "\n";

            foreach ($results as $row) {
                foreach ($row as $value) {
                    echo str_pad($value ?? 'NULL', 15) . " | ";
                }
                echo "\n";
            }
        }

        echo "\n";

        self::analyzeExplain($results);
    }

    private static function analyzeExplain(array $results): void
    {
        echo "📊 ANALYSIS:\n";
        echo str_repeat('-', 80) . "\n";

        foreach ($results as $row) {
            $warnings = [];
            $recommendations = [];

            if (in_array($row['type'], ['ALL', 'index'])) {
                $warnings[] = "Type '{$row['type']}' means full table scan";
                $recommendations[] = "Consider adding indexes";
            } elseif (in_array($row['type'], ['ref', 'eq_ref', 'const'])) {
                echo "Good access type: {$row['type']}\n";
            }

            if (isset($row['rows']) && $row['rows'] > 10000) {
                $warnings[] = "Scanning {$row['rows']} rows - might be slow";
            }

            if (isset($row['Extra'])) {
                if (strpos($row['Extra'], 'Using filesort') !== false) {
                    $warnings[] = "Using filesort - consider adding index for ORDER BY";
                }
                if (strpos($row['Extra'], 'Using temporary') !== false) {
                    $warnings[] = "Using temporary table - might be slow";
                }
                if (strpos($row['Extra'], 'Using index') !== false) {
                    echo "Using covering index\n";
                }
            }

            foreach ($warnings as $warning) {
                echo $warning . "\n";
            }

            foreach ($recommendations as $rec) {
                echo "💡 " . $rec . "\n";
            }
        }

        echo "\n";
    }
}