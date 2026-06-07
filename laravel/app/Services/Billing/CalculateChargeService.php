<?php

namespace App\Services\Billing;

use App\Models\ChargeModel;
use App\Services\Billing\DTOs\ChargeCalculationResult;
use App\Services\Billing\Exceptions\ChargeCalculationException;
use App\Support\Money;

class CalculateChargeService
{
    /**
     * Executes the calculation strategy for a given charge model.
     * Returns a normalized result DTO containing precise financial values.
     */
    public function calculate(
        ChargeModel $chargeModel,
        array $context = []
    ): ChargeCalculationResult {

        if (! $chargeModel->isActive()) {
            throw new ChargeCalculationException('Charge model is inactive.');
        }

        if (! $chargeModel->isCurrentlyEffective()) {
            throw new ChargeCalculationException('Charge model is not currently effective.');
        }

        $subtotal = match ($chargeModel->pricing_strategy) {
            ChargeModel::STRATEGY_AGREEMENT_RENT => $this->calculateAgreementRent($context),
            ChargeModel::STRATEGY_FLAT_FEE => $this->calculateFlatFee($context),
            ChargeModel::STRATEGY_FIXED => $this->calculateFixed($chargeModel, $context),
            ChargeModel::STRATEGY_METERED => $this->calculateMetered($chargeModel, $context),
            ChargeModel::STRATEGY_PERCENTAGE => $this->calculatePercentage($chargeModel, $context),
            ChargeModel::STRATEGY_TIERED => $this->calculateTiered($chargeModel, $context),
            ChargeModel::STRATEGY_FORMULA => $this->calculateFormula($chargeModel, $context),
            default => throw new ChargeCalculationException('Unsupported pricing strategy.'),
        };

        $subtotal = $this->applyLimits($subtotal, $chargeModel);
        $taxAmount = $this->calculateTax($subtotal, $chargeModel);
        $totalAmount = Money::add($subtotal, $taxAmount);

        return new ChargeCalculationResult(
            amount: (float) Money::toScale($subtotal, 2),
            subtotal: (float) Money::toScale($subtotal, 2),
            taxAmount: (float) Money::toScale($taxAmount, 2),
            totalAmount: (float) Money::toScale($totalAmount, 2),
            breakdown: [
                'strategy' => $chargeModel->pricing_strategy,
                'currency' => $chargeModel->currency ?? 'USD',
                'calculated_at' => now()->toIso8601String(),
                'engine_ver' => '2.0.1',
            ]
        );
    }

    protected function calculateAgreementRent(array $context): string
    {
        $rent = $context['monthly_rent'] ?? null;

        if (blank($rent)) {
            throw new ChargeCalculationException(
                'Monthly rent must be set on the rental agreement.'
            );
        }

        return Money::toScale((string) $rent, 2);
    }

    protected function calculateFlatFee(array $context): string
    {
        $amount = $context['override_amount']
            ?? $context['flat_amount']
            ?? null;

        if (blank($amount)) {
            throw new ChargeCalculationException(
                'Flat fee amount must be set on the agreement charge line.'
            );
        }

        return Money::toScale((string) $amount, 2);
    }

    /**
     * Legacy fixed strategy: agreement override first, then optional model default.
     */
    protected function calculateFixed(ChargeModel $chargeModel, array $context): string
    {
        $override = $context['override_amount'] ?? $context['flat_amount'] ?? null;

        if (! blank($override)) {
            return Money::toScale((string) $override, 2);
        }

        if (! blank($chargeModel->base_amount)) {
            return Money::toScale((string) $chargeModel->base_amount, 2);
        }

        throw new ChargeCalculationException(
            'No fixed amount: set override on the agreement charge or a default on the charge model.'
        );
    }

    protected function calculateMetered(ChargeModel $chargeModel, array $context): string
    {
        $consumption = (string) ($context['consumption'] ?? '0');
        $unitRate = (string) $chargeModel->unit_rate;

        return Money::toScale(Money::mul($consumption, $unitRate), 2);
    }

    protected function calculatePercentage(ChargeModel $chargeModel, array $context): string
    {
        $baseAmount = (string) ($context['base_amount'] ?? '0');
        $rate = Money::div((string) $chargeModel->percentage_rate, '100');

        return Money::toScale(Money::mul($baseAmount, $rate), 2);
    }

    protected function calculateTiered(ChargeModel $chargeModel, array $context): string
    {
        $usage = (string) ($context['consumption'] ?? '0');
        $tiers = $chargeModel->tier_configuration ?? [];
        $amount = Money::zero();

        foreach ($tiers as $tier) {
            $from = (string) ($tier['from'] ?? '0');
            $to = isset($tier['to']) ? (string) $tier['to'] : null;
            $rate = (string) ($tier['rate'] ?? '0');

            if (Money::comp($usage, $from) <= 0) {
                continue;
            }

            $cap = $to !== null ? Money::min($usage, $to) : $usage;
            $units = Money::sub($cap, $from);

            if (Money::comp($units, '0') > 0) {
                $amount = Money::add($amount, Money::mul($units, $rate));
            }
        }

        return Money::toScale($amount, 2);
    }

    protected function calculateFormula(ChargeModel $chargeModel, array $context): string
    {
        throw new ChargeCalculationException('Formula engine not implemented yet.');
    }

    protected function applyLimits(string $amount, ChargeModel $chargeModel): string
    {
        if ($chargeModel->minimum_amount) {
            $amount = Money::max(
                $amount,
                Money::toScale((string) $chargeModel->minimum_amount, 2)
            );
        }

        if ($chargeModel->maximum_amount) {
            $amount = Money::min(
                $amount,
                Money::toScale((string) $chargeModel->maximum_amount, 2)
            );
        }

        return Money::toScale($amount, 2);
    }

    protected function calculateTax(string $subtotal, ChargeModel $chargeModel): string
    {
        if (! $chargeModel->taxable || Money::comp((string) $chargeModel->tax_rate, '0') <= 0) {
            return Money::toScale('0', 2);
        }

        $rate = Money::div((string) $chargeModel->tax_rate, '100');

        return Money::toScale(Money::mul($subtotal, $rate), 2);
    }
}
