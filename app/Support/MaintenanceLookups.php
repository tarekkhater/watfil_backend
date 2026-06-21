<?php

namespace App\Support;

class MaintenanceLookups
{
    /** @return list<string> */
    public static function values(string $key): array
    {
        return collect(config("maintenance.{$key}", []))
            ->pluck('value')
            ->filter()
            ->values()
            ->all();
    }

    public static function label(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $item = collect(config("maintenance.{$key}", []))
            ->firstWhere('value', $value);

        return $item['label_ar'] ?? $value;
    }
}
