<?php

namespace App\Support;

use RuntimeException;

final class CsvData
{
    public static function read(string $file): array
    {
        if (! preg_match('/^[a-z_]+\.csv$/D', $file)) {
            throw new RuntimeException('Invalid seed data filename.');
        }
        $handle = fopen(database_path('data/'.$file), 'rb');
        if (! $handle) {
            throw new RuntimeException('Seed data is unavailable: '.$file);
        }
        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');
            if (! $headers || count(array_unique($headers)) !== count($headers)) {
                throw new RuntimeException('Invalid CSV header: '.$file);
            }
            $rows = [];
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($row === [null]) {
                    continue;
                }
                if (count($row) !== count($headers) || count($rows) >= 1000) {
                    throw new RuntimeException('Invalid CSV row or row limit: '.$file);
                }
                $rows[] = array_combine($headers, $row);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
