<?php

namespace App\Services\Accounting;

use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChartOfAccountsService
{
    /** @return array<int, array{code: string, name: string, type: string, sort_order: int}> */
    public static function defaultAccounts(): array
    {
        return [
            ['code' => Account::CODE_CASH, 'name' => 'Cash and Cash Equivalents', 'type' => Account::TYPE_ASSET, 'sort_order' => 110],
            ['code' => Account::CODE_BANK, 'name' => 'Bank Accounts', 'type' => Account::TYPE_ASSET, 'sort_order' => 115],
            ['code' => Account::CODE_MOBILE_MONEY, 'name' => 'Mobile Money Wallets', 'type' => Account::TYPE_ASSET, 'sort_order' => 116],
            ['code' => Account::CODE_CHEQUE_IN_TRANSIT, 'name' => 'Cheques in Transit', 'type' => Account::TYPE_ASSET, 'sort_order' => 117],
            ['code' => Account::CODE_ACCOUNTS_RECEIVABLE, 'name' => 'Accounts Receivable', 'type' => Account::TYPE_ASSET, 'sort_order' => 120],
            ['code' => Account::CODE_CUSTOMER_DEPOSITS_PAYABLE, 'name' => 'Customer Deposits Payable', 'type' => Account::TYPE_LIABILITY, 'sort_order' => 210],
            ['code' => Account::CODE_DEFERRED_REVENUE, 'name' => 'Deferred Revenue', 'type' => Account::TYPE_LIABILITY, 'sort_order' => 220],
            ['code' => Account::CODE_RENTAL_INCOME, 'name' => 'Rental Income', 'type' => Account::TYPE_REVENUE, 'sort_order' => 410],
            ['code' => Account::CODE_UTILITY_INCOME, 'name' => 'Utility Recovery Income', 'type' => Account::TYPE_REVENUE, 'sort_order' => 411],
            ['code' => Account::CODE_SERVICE_INCOME, 'name' => 'Service Charge Income', 'type' => Account::TYPE_REVENUE, 'sort_order' => 414],
            ['code' => Account::CODE_SALE_INCOME, 'name' => 'Property Sale Revenue', 'type' => Account::TYPE_REVENUE, 'sort_order' => 415],
        ];
    }

    public function resolvePostingAccount(string $companyId, string $standardCode): Account
    {
        $account = Account::query()
            ->where('company_id', $companyId)
            ->where('code', $standardCode)
            ->where('status', Account::STATUS_ACTIVE)
            ->first();

        if ($account) {
            return $account;
        }

        foreach (Account::LEGACY_CODE_ALIASES as $legacyCode => $mappedCode) {
            if ($mappedCode !== $standardCode) {
                continue;
            }

            $legacy = Account::query()
                ->where('company_id', $companyId)
                ->where('code', $legacyCode)
                ->where('status', Account::STATUS_ACTIVE)
                ->first();

            if ($legacy) {
                return $legacy;
            }
        }

        throw new BusinessRuleException(
            "Posting account {$standardCode} is not configured. Run accounting:seed-chart.",
            'ACCOUNT_NOT_CONFIGURED',
        );
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function seedDefaults(Company $company, ?string $userId = null): array
    {
        return DB::transaction(function () use ($company, $userId) {
            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach (self::defaultAccounts() as $definition) {
                $account = Account::query()
                    ->withTrashed()
                    ->where('company_id', $company->id)
                    ->where('code', $definition['code'])
                    ->first();

                if ($account) {
                    if ($account->trashed()) {
                        $account->restore();
                    }

                    $changes = $this->syncSystemAccountDefinition($account, $definition, $userId);

                    if ($changes === []) {
                        $skipped++;
                    } else {
                        $account->update($changes);
                        $updated++;
                    }

                    continue;
                }

                Account::query()->create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'code' => $definition['code'],
                    'name' => $definition['name'],
                    'type' => $definition['type'],
                    'is_system' => true,
                    'sort_order' => $definition['sort_order'],
                    'status' => Account::STATUS_ACTIVE,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $created++;
            }

            return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
        });
    }

    /**
     * @param  array{code: string, name: string, type: string, sort_order: int}  $definition
     * @return array<string, mixed>
     */
    protected function syncSystemAccountDefinition(Account $account, array $definition, ?string $userId): array
    {
        $changes = [];

        if ($account->name !== $definition['name']) {
            $changes['name'] = $definition['name'];
        }

        if ((int) $account->sort_order !== (int) $definition['sort_order']) {
            $changes['sort_order'] = $definition['sort_order'];
        }

        if (! $account->is_system) {
            $changes['is_system'] = true;
        }

        if ($account->status !== Account::STATUS_ACTIVE) {
            $changes['status'] = Account::STATUS_ACTIVE;
        }

        if ($changes !== [] && $userId) {
            $changes['updated_by'] = $userId;
        }

        return $changes;
    }

    public function create(array $data, string $companyId, string $userId): Account
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            return Account::query()->create([
                'id' => (string) Str::uuid(),
                'company_id' => $companyId,
                'code' => strtoupper(trim((string) $data['code'])),
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'is_system' => false,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status' => $data['status'] ?? Account::STATUS_ACTIVE,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    public function update(Account $account, array $data, string $userId): Account
    {
        return DB::transaction(function () use ($account, $data, $userId) {
            if ($account->is_system) {
                unset($data['code'], $data['type']);
            }

            $data['updated_by'] = $userId;

            if (isset($data['code'])) {
                $data['code'] = strtoupper(trim((string) $data['code']));
            }

            $account->update($data);

            return $account->fresh();
        });
    }

    public function delete(Account $account): bool
    {
        if ($account->is_system) {
            throw new BusinessRuleException(
                'System accounts cannot be deleted.',
                'ACCOUNT_SYSTEM_PROTECTED',
            );
        }

        return DB::transaction(fn () => (bool) $account->delete());
    }
}
