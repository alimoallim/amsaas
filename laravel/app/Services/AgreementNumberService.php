<?php

namespace App\Services;

use App\Models\Agreement;
use Illuminate\Support\Facades\DB;

class AgreementNumberService
{
    /**
     * Allocate a unique agreement number (globally unique column).
     */
    public static function allocate(string $agreementType): string
    {
        return DB::transaction(function () use ($agreementType) {
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $candidate = self::nextCandidate($agreementType);

                $exists = Agreement::withoutGlobalScopes()
                    ->withTrashed()
                    ->where('agreement_number', $candidate)
                    ->exists();

                if (! $exists) {
                    return $candidate;
                }
            }

            throw new \RuntimeException(
                'Unable to allocate a unique agreement number after multiple attempts.'
            );
        });
    }

    /**
     * @deprecated Use allocate() for new agreements.
     */
    public static function generate(
        string $agreementType,
        ?string $companyId = null,
    ): string {
        return self::allocate($agreementType);
    }

    protected static function nextCandidate(string $agreementType): string
    {
        $prefix = match ($agreementType) {
            Agreement::TYPE_RENTAL => 'RA',
            Agreement::TYPE_SALE => 'SA',
            default => 'AG',
        };

        $year = now()->format('Y');
        $pattern = "{$prefix}-{$year}-%";

        $numbers = Agreement::withoutGlobalScopes()
            ->withTrashed()
            ->where('agreement_type', $agreementType)
            ->where('agreement_number', 'like', $pattern)
            ->lockForUpdate()
            ->pluck('agreement_number');

        $maxSequence = 0;

        foreach ($numbers as $number) {
            $sequence = self::extractSequence($number);
            if ($sequence > $maxSequence) {
                $maxSequence = $sequence;
            }
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $maxSequence + 1);
    }

    protected static function extractSequence(string $agreementNumber): int
    {
        $parts = explode('-', $agreementNumber);

        return (int) end($parts);
    }
}
