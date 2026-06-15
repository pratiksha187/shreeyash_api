<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyDieselPurchase;
use App\Models\LabourSite;
use App\Support\Tenant;
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
        $sites = LabourSite::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $entries = DailyDieselPurchase::query()
            ->forCurrentCompany()
            ->with('siteEntries')
            ->whereBetween('entry_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn (DailyDieselPurchase $entry) => $entry->entry_date->toDateString());
        $rows = $this->buildRows($monthStart, $monthEnd, $entries, $sites);

        return view('admin.diesel-purchases.index', [
            'selectedMonth' => $selectedMonth,
            'monthLabel' => $monthStart->format('M'),
            'sites' => $sites,
            'rows' => $rows,
            'summary' => [
                'diesel_ltr' => $rows->sum('diesel_ltr'),
                'amount' => $rows->sum('amount'),
                'sites' => $sites->mapWithKeys(fn (LabourSite $site) => [
                    $site->id => [
                        'name' => $site->name,
                        'today_supply' => $rows->sum(fn (array $row) => $row['sites'][$site->id]['today_supply'] ?? 0),
                        'used' => $rows->sum(fn (array $row) => $row['sites'][$site->id]['used'] ?? 0),
                    ],
                ]),
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
            'entries.*.sites' => ['nullable', 'array'],
            'entries.*.sites.*.labour_site_id' => ['required', 'exists:labour_sites,id'],
            'entries.*.sites.*.opening_balance' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.sites.*.today_supply' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.sites.*.used' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        foreach ($data['entries'] as $entry) {
            $this->saveEntry($entry);
        }

        return redirect()
            ->route('admin.diesel-purchases.index', ['month' => $data['month']])
            ->with('success', 'Daily diesel purchase sheet saved successfully.');
    }

    private function buildRows(Carbon $monthStart, Carbon $monthEnd, $entries, $sites)
    {
        $siteCarry = $sites->mapWithKeys(fn (LabourSite $site) => [$site->id => 0.0])->all();

        return collect(CarbonPeriod::create($monthStart, $monthEnd))
            ->values()
            ->map(function (Carbon $date, int $index) use ($entries, $sites, &$siteCarry) {
                $entry = $entries->get($date->toDateString());
                $dieselLtr = (float) ($entry?->diesel_ltr ?? 0);
                $rate = (float) ($entry?->rate ?? 0);
                $siteEntries = $entry?->siteEntries?->keyBy('labour_site_id') ?? collect();
                $siteRows = [];

                foreach ($sites as $site) {
                    $siteEntry = $siteEntries->get($site->id);
                    $opening = $siteEntry && $siteEntry->opening_balance !== null
                        ? (float) $siteEntry->opening_balance
                        : ($siteCarry[$site->id] ?? 0.0);
                    $supply = (float) ($siteEntry?->today_supply ?? 0);
                    $used = (float) ($siteEntry?->used ?? 0);
                    $total = $opening + $supply;
                    $balance = max(0, $total - $used);

                    $siteCarry[$site->id] = $balance;
                    $siteRows[$site->id] = [
                        'id' => $site->id,
                        'name' => $site->name,
                        'opening_balance' => $opening,
                        'opening_is_manual' => $siteEntry && $siteEntry->opening_balance !== null,
                        'today_supply' => $supply,
                        'total' => $total,
                        'used' => $used,
                        'balance' => $balance,
                    ];
                }

                return [
                    'sr_no' => $index + 1,
                    'date' => $date->copy(),
                    'entry' => $entry,
                    'challan_no' => $entry?->challan_no,
                    'campar' => $entry?->campar,
                    'diesel_ltr' => $dieselLtr,
                    'rate' => $rate,
                    'amount' => round($dieselLtr * $rate),
                    'sites' => $siteRows,
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

        $purchase = DailyDieselPurchase::query()->updateOrCreate(
            [
                'company_id' => app(Tenant::class)->id(),
                'entry_date' => $entryDate,
            ],
            [
                'challan_no' => $entry['challan_no'] ?? null,
                'campar' => $entry['campar'] ?? null,
                'diesel_ltr' => $entry['diesel_ltr'] ?? 0,
                'rate' => $entry['rate'] ?? 0,
            ]
        );

        foreach ($entry['sites'] ?? [] as $siteEntry) {
            if (! $this->siteEntryHasData($siteEntry)) {
                continue;
            }

            $purchase->siteEntries()->updateOrCreate(
                [
                    'company_id' => app(Tenant::class)->id(),
                    'labour_site_id' => $siteEntry['labour_site_id'],
                ],
                [
                    'opening_balance' => $siteEntry['opening_balance'] ?? null,
                    'today_supply' => $siteEntry['today_supply'] ?? 0,
                    'used' => $siteEntry['used'] ?? 0,
                ]
            );
        }
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
        ] as $field) {
            if ((float) ($entry[$field] ?? 0) > 0) {
                return true;
            }
        }

        foreach ($entry['sites'] ?? [] as $siteEntry) {
            if ($this->siteEntryHasData($siteEntry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $siteEntry
     */
    private function siteEntryHasData(array $siteEntry): bool
    {
        foreach (['opening_balance', 'today_supply', 'used'] as $field) {
            if ((float) ($siteEntry[$field] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
