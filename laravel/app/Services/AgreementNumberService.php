<?php
namespace App\Services;
use App\Models\Agreement;
class AgreementNumberService
{
    /*
    |--------------------------------------------------------------------------
    | Generate Agreement Number
    |--------------------------------------------------------------------------
    */

    public static function generate(
        string $agreementType
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Prefix
        |--------------------------------------------------------------------------
        */

        $prefix = match (
            $agreementType
        ) {

            Agreement::TYPE_RENTAL =>
                'RA',

            Agreement::TYPE_SALE =>
                'SA',

            default =>
                'AG',
        };

        /*
        |--------------------------------------------------------------------------
        | Current Year
        |--------------------------------------------------------------------------
        */

        $year = now()->format('Y');

        /*
        |--------------------------------------------------------------------------
        | Latest Agreement
        |--------------------------------------------------------------------------
        */

        $latestAgreement = Agreement::query()

            ->where(
                'agreement_type',
                $agreementType
            )

            ->whereYear(
                'created_at',
                $year
            )

            ->latest('created_at')

            ->first();

        /*
        |--------------------------------------------------------------------------
        | Extract Sequence
        |--------------------------------------------------------------------------
        */

        $nextSequence = 1;

        if (

            $latestAgreement
            &&
            $latestAgreement->agreement_number

        ) {

            $parts = explode(

                '-',

                $latestAgreement
                    ->agreement_number
            );

            $lastSequence = intval(

                end($parts)
            );

            $nextSequence =
                $lastSequence + 1;
        }

        /*
        |--------------------------------------------------------------------------
        | Format
        |--------------------------------------------------------------------------
        */

        return sprintf(

            '%s-%s-%05d',

            $prefix,

            $year,

            $nextSequence
        );
    }
}