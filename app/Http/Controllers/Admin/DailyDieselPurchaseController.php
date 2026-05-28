<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyDieselPurchase;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyDieselPurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $selectedMonth = $filters['month'] ?? now()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $entries = DailyDieselPurchase::query()
            ->whereBetween('entry_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn (DailyDieselPurchase $entry) => $entry->entry_date->toDateString());
        $rows = $this->buildRows($monthStart, $monthEnd, $entries);

        return view('admin.diesel-purchases.index', [
            'selectedMonth' => $selectedMonth,
            'monthLabel' => $monthStart->format('M'),
            'rows' => $rows,
            'summary' => [
                'diesel_ltr' => $rows->sum('diesel_ltr'),
                'amount' => $rows->sum('amount'),
                'khanav_supply' => $rows->sum('khanav_today_supply'),
                'khalapur_supply' => $rows->sum('khalapur_today_supply'),
                'khanav_used' => $rows->sum('khanav_used'),
                'khalapur_used' => $rows->sum('khalapur_used'),
            ],
        ]);
    }

    public function storeMonthly(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'entries' => ['required', 'array'],
            'entries.*.entry_date' => ['required', 'date'],
            'entries.*.challan_no' => ['nullable', 'string', 'max:100'],
            'entries.*.campar' => ['nullable', 'string', 'max:100'],
            'entries.*.diesel_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.khanav_opening_balance' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.khanav_today_supply' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.khanav_used' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.khalapur_opening_balance' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.khalapur_today_supply' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.khalapur_used' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        foreach ($data['entries'] as $entry) {
            $this->saveEntry($entry);
        }

        return redirect()
            ->route('admin.diesel-purchases.index', ['month' => $data['month']])
            ->with('success', 'Daily diesel purchase sheet saved successfully.');
    }

    private function buildRows(Carbon $monthStart, Carbon $monthEnd, $entries)
    {
        $khanavCarry = 0.0;
        $khalapurCarry = 0.0;

        return collect(CarbonPeriod::create($monthStart, $monthEnd))
            ->values()
            ->map(function (Carbon $date, int $index) use ($entries, &$khanavCarry, &$khalapurCarry) {
                $entry = $entries->get($date->toDateString());
                $dieselLtr = (float) ($entry?->diesel_ltr ?? 0);
                $rate = (float) ($entry?->rate ?? 0);
                $khanavOpening = $entry && $entry->khanav_opening_balance !== null
                    ? (float) $entry->khanav_opening_balance
                    : $khanavCarry;
                $khanavSupply = (float) ($entry?->khanav_today_supply ?? 0);
                $khanavUsed = (float) ($entry?->khanav_used ?? 0);
                $khanavTotal = $khanavOpening + $khanavSupply;
                $khanavBalance = max(0, $khanavTotal - $khanavUsed);
                $khalapurOpening = $entry && $entry->khalapur_opening_balance !== null
                    ? (float) $entry->khalapur_opening_balance
                    : $khalapurCarry;
                $khalapurSupply = (float) ($entry?->khalapur_today_supply ?? 0);
                $khalapurUsed = (float) ($entry?->khalapur_used ?? 0);
                $khalapurTotal = $khalapurOpening + $khalapurSupply;
                $khalapurBalance = max(0, $khalapurTotal - $khalapurUsed);

                $khanavCarry = $khanavBalance;
                $khalapurCarry = $khalapurBalance;

                return [
                    'sr_no' => $index + 1,
                    'date' => $date->copy(),
                    'entry' => $entry,
                    'challan_no' => $entry?->challan_no,
                    'campar' => $entry?->campar,
                    'diesel_ltr' => $dieselLtr,
                    'rate' => $rate,
                    'amount' => round($dieselLtr * $rate),
                    'khanav_opening_balance' => $khanavOpening,
                    'khanav_opening_is_manual' => $entry && $entry->khanav_opening_balance !== null,
                    'khanav_today_supply' => $khanavSupply,
                    'khanav_total' => $khanavTotal,
                    'khanav_used' => $khanavUsed,
                    'khanav_balance' => $khanavBalance,
                    'khalapur_opening_balance' => $khalapurOpening,
                    'khalapur_opening_is_manual' => $entry && $entry->khalapur_opening_balance !== null,
                    'khalapur_today_supply' => $khalapurSupply,
                    'khalapur_total' => $khalapurTotal,
                    'khalapur_used' => $khalapurUsed,
                    'khalapur_balance' => $khalapurBalance,
                ];
            });
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function saveEntry(array $entry): void
    {
        $entryDate = Carbon::parse($entry['entry_date'])->toDateString();

        if (! $this->entryHasData($entry)) {
            return;
        }

        DailyDieselPurchase::query()->updateOrCreate(
            ['entry_date' => $entryDate],
            [
                'challan_no' => $entry['challan_no'] ?? null,
                'campar' => $entry['campar'] ?? null,
                'diesel_ltr' => $entry['diesel_ltr'] ?? 0,
                'rate' => $entry['rate'] ?? 0,
                'khanav_opening_balance' => $entry['khanav_opening_balance'] ?? null,
                'khanav_today_supply' => $entry['khanav_today_supply'] ?? 0,
                'khanav_used' => $entry['khanav_used'] ?? 0,
                'khalapur_opening_balance' => $entry['khalapur_opening_balance'] ?? null,
                'khalapur_today_supply' => $entry['khalapur_today_supply'] ?? 0,
                'khalapur_used' => $entry['khalapur_used'] ?? 0,
            ]
        );
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function entryHasData(array $entry): bool
    {
        foreach (['challan_no', 'campar'] as $field) {
            if (filled($entry[$field] ?? null)) {
                return true;
            }
        }

        foreach ([
            'diesel_ltr',
            'rate',
            'khanav_opening_balance',
            'khanav_today_supply',
            'khanav_used',
            'khalapur_opening_balance',
            'khalapur_today_supply',
            'khalapur_used',
        ] as $field) {
            if ((float) ($entry[$field] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
