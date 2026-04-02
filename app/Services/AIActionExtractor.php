<?php

namespace App\Services;

class AIActionExtractor
{
    private const SERVICE_TYPES = [
        'repair' => ['repair', 'fix', 'fixing', 'repaired', 'fixed', 'broken', 'malfunction'],
        'maintenance' => ['maintenance', 'service', 'checkup', 'inspection', 'cleaning', 'cleaned', 'maintain'],
        'installation' => ['install', 'installation', 'installed', 'setup', 'set up', 'new unit'],
        'inspection' => ['inspect', 'inspection', 'diagnose', 'diagnosis', 'checked', 'evaluate'],
    ];

    private const ISSUE_TYPES = [
        'ac_not_cooling' => ['not cooling', 'no cooling', 'warm air', 'hot air', 'blowing warm', 'blowing hot', 'weak airflow'],
        'ac_not_heating' => ['not heating', 'no heat', 'cold air', 'blowing cold', 'blowing cool'],
        'leak' => ['leak', 'leaking', 'leaked', 'water', 'dripping', 'drip', 'moisture', 'condensation'],
        'electrical' => ['electrical', 'electric', 'spark', 'short', 'breaker', 'fuse', 'power', 'won\'t turn on', 'won\'t start'],
        'noise' => ['noise', 'noisy', 'loud', 'vibrating', 'rattling', 'grinding', 'squealing', 'squeaking'],
        'compressor' => ['compressor', 'compressors'],
        'refrigerant' => ['refrigerant', 'r410a', 'r22', 'r134a', 'freon', 'low coolant', 'recharge'],
        'capacitor' => ['capacitor', 'capacitors', 'start capacitor', 'run capacitor'],
        'thermostat' => ['thermostat', 'thermostats', 'temperature', 'sensor'],
        'filter' => ['filter', 'filters', 'airflow', 'restricted'],
        'fan' => ['fan', 'blower', 'motor', 'impeller'],
        'drain' => ['drain', 'clogged', 'overflow', 'blocked', 'drainage'],
    ];

    private const PARTS_PATTERNS = [
        'capacitor' => ['capacitor \d+[µ]?F?', 'capacitor', '\d+[µ]?F capacitor'],
        'refrigerant' => ['refrigerant [A-Z0-9]+', 'r[0-9]+[a-z]', 'freon'],
        'compressor' => ['compressor'],
        'fan_motor' => ['fan motor', 'blower motor', 'motor'],
        'filter' => ['filter', 'air filter'],
        'thermostat' => ['thermostat', 'thermistor', 'temperature sensor'],
        'contactor' => ['contactor'],
        'relay' => ['relay', 'start relay'],
        'valve' => ['valve', 'expansion valve', 'service valve', 'tXV'],
        ' coil' => ['evaporator coil', 'condenser coil', 'coil'],
    ];

    private const ACTIONS = [
        'replaced' => ['replaced', 'swap', 'swapped', 'changed', 'exchanged'],
        'repaired' => ['repaired', 'fixed', 'sealed', 'patched'],
        'cleaned' => ['cleaned', 'flushed', 'rinsed'],
        'recharged' => ['recharged', 'refilled', 'added refrigerant', 'topped off'],
        'adjusted' => ['adjusted', 'calibrated', 'recalibrated'],
        'inspected' => ['inspected', 'checked', 'tested', 'diagnosed', 'evaluated'],
        'installed' => ['installed', 'mounted', 'set up', 'fitted'],
        'evacuated' => ['evacuated', 'vacuumed', 'pulled vacuum'],
    ];

    private const PRICE_PATTERN = '/\$?\s*(\d+(?:,\d{3})*(?:\.\d{2})?)/';

    public function extract(string $transcribedText): array
    {
        $text = strtolower($transcribedText);
        $textClean = preg_replace('/[^\w\s\$\.\,]/', ' ', $text);
        $textClean = preg_replace('/\s+/', ' ', $textClean);

        return [
            'service_type' => $this->extractServiceType($textClean),
            'issue_type' => $this->extractIssueType($textClean),
            'action_taken' => $this->extractActionTaken($textClean),
            'parts_used' => $this->extractPartsUsed($textClean),
            'estimated_price' => $this->extractPrice($text),
            'next_steps' => $this->extractNextSteps($textClean),
        ];
    }

    private function extractServiceType(string $text): ?string
    {
        $scores = [];

        foreach (self::SERVICE_TYPES as $type => $keywords) {
            $scores[$type] = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $scores[$type]++;
                }
            }
        }

        $maxScore = max($scores);
        if ($maxScore === 0) {
            return null;
        }

        return array_keys($scores, $maxScore)[0];
    }

    private function extractIssueType(string $text): ?string
    {
        $scores = [];

        foreach (self::ISSUE_TYPES as $type => $keywords) {
            $scores[$type] = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $scores[$type]++;
                }
            }
        }

        $maxScore = max($scores);
        if ($maxScore === 0) {
            return null;
        }

        return array_keys($scores, $maxScore)[0];
    }

    private function extractActionTaken(string $text): ?string
    {
        foreach (self::ACTIONS as $action => $verbs) {
            foreach ($verbs as $verb) {
                if (str_contains($text, $verb)) {
                    $parts = $this->extractPartsUsed($text);
                    $partsStr = !empty($parts) ? ' ' . implode(', ', $parts) : '';
                    return ucfirst($action) . $partsStr;
                }
            }
        }

        return null;
    }

    private function extractPartsUsed(string $text): array
    {
        $parts = [];
        $seen = [];

        foreach (self::PARTS_PATTERNS as $partCategory => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match('/' . preg_quote($pattern, '/') . '/i', $text, $matches)) {
                    $normalized = trim($matches[0]);
                    if (!isset($seen[$normalized]) && strlen($normalized) > 2) {
                        $parts[] = $normalized;
                        $seen[$normalized] = true;
                    }
                }
            }
        }

        return array_unique($parts);
    }

    private function extractPrice(string $text): ?float
    {
        if (preg_match_all(self::PRICE_PATTERN, $text, $matches)) {
            $prices = array_map(function ($m) {
                return (float) str_replace(',', '', $m);
            }, $matches[1]);

            return max($prices);
        }

        return null;
    }

    private function extractNextSteps(string $text): ?string
    {
        $indicators = [
            'follow up',
            'follow-up',
            'next appointment',
            'schedule',
            'need to',
            'should',
            'will need',
            'call back',
            'warranty',
            'come back',
            'return',
        ];

        foreach ($indicators as $indicator) {
            if (str_contains($text, $indicator)) {
                $sentences = preg_split('/[.!?]+/', $text);
                foreach ($sentences as $sentence) {
                    if (str_contains($sentence, $indicator)) {
                        $trimmed = trim(ucfirst($sentence));
                        if (strlen($trimmed) > 10) {
                            return $trimmed;
                        }
                    }
                }
            }
        }

        return null;
    }
}
