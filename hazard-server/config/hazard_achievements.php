<?php

use App\Support\CsvData;

$names = [];
$thresholds = [];
foreach (CsvData::read('hazard_achievements.csv') as $row) {
    if (! preg_match('/^[a-z]+$/D', $row['category_id']) || ! ctype_digit($row['threshold']) || (int) $row['threshold'] < 1 || trim($row['name']) === '') {
        throw new RuntimeException('Invalid hazard achievement catalog.');
    }
    if (isset($names[$row['category_id']][(int) $row['threshold']])) {
        throw new RuntimeException('Duplicate hazard achievement threshold.');
    }
    $names[$row['category_id']][(int) $row['threshold']] = $row['name'];
    $thresholds[] = (int) $row['threshold'];
}
$thresholds = array_values(array_unique($thresholds));
sort($thresholds);
foreach ($names as &$levels) {
    ksort($levels);
    if (array_keys($levels) !== $thresholds) {
        throw new RuntimeException('Each hazard category needs a name for every achievement threshold.');
    }
    $levels = array_values($levels);
}
unset($levels);

return ['thresholds' => $thresholds, 'names' => $names];
