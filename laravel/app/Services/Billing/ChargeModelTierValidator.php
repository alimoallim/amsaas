<?php

namespace App\Services\Billing;

class ChargeModelTierValidator
{
    /**
     * @param  array<int, array<string, mixed>>|null  $tiers
     * @return list<string>
     */
    public static function errors(?array $tiers): array
    {
        if ($tiers === null || $tiers === []) {
            return ['At least one tier is required.'];
        }

        $errors = [];

        foreach ($tiers as $index => $tier) {
            $from = $tier['from'] ?? null;
            $to = $tier['to'] ?? null;
            $rate = $tier['rate'] ?? null;

            if ($from === null || $from === '' || $rate === null || $rate === '') {
                $errors[] = 'Each tier requires from and rate values.';

                continue;
            }

            if ($to !== null && $to !== '' && (float) $from > (float) $to) {
                $errors[] = sprintf('Tier %d: "from" must be less than or equal to "to".', $index + 1);
            }

            if ($index > 0) {
                $previous = $tiers[$index - 1];
                $previousTo = $previous['to'] ?? null;

                if ($previousTo === null || $previousTo === '') {
                    $errors[] = sprintf('Tier %d: close the previous open-ended tier before adding another.', $index + 1);

                    continue;
                }

                if ((float) $previousTo + 1 !== (float) $from) {
                    $errors[] = 'Tiers must be contiguous and non-overlapping.';
                }
            }
        }

        return array_values(array_unique($errors));
    }
}
