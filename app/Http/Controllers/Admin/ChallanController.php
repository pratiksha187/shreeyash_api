<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChallanController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'user_id' => ['nullable', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'vehicle_no' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $baseQuery = $this->filteredQuery($filters);

        $challans = (clone $baseQuery)
            ->with('user:id,name,mobile,designation')
            ->paginate(20)
            ->withQueryString();

        return view('admin.challans.index', [
            'challans' => $challans,
            'filters' => $filters,
            'employees' => User::query()->orderBy('name')->get(['id', 'name', 'mobile']),
            'locations' => Challan::query()
                ->whereNotNull('location')
                ->distinct()
                ->orderBy('location')
                ->pluck('location'),
            'vehicles' => Challan::query()
                ->whereNotNull('vehicle_no')
                ->distinct()
                ->orderBy('vehicle_no')
                ->pluck('vehicle_no'),
            'summary' => [
                'total_challans' => (clone $baseQuery)->count(),
                'employees' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
                'vehicles' => (clone $baseQuery)->distinct('vehicle_no')->count('vehicle_no'),
                'latest_date' => (clone $baseQuery)->max('challan_date'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'user_id' => ['nullable', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'vehicle_no' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $challans = $this->filteredQuery($filters)
            ->with('user:id,name,mobile,designation')
            ->get();
        $filename = 'challans-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($challans) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Challan No.',
                'Date',
                'Name Of Party',
                'Material / M/c',
                'Vehicle No.',
                'Measurement',
                'Location',
                'Time',
                'Receiver Name',
                'Driver Name',
                'Submitted By',
            ]);

            foreach ($challans as $challan) {
                fputcsv($handle, [
                    $challan->challan_no,
                    $challan->challan_date?->format('d/m/Y') ?? '',
                    $challan->party_name,
                    $challan->material_machine,
                    $challan->vehicle_no,
                    $challan->measurement,
                    $challan->location,
                    $challan->delivery_time,
                    $challan->receiver_name,
                    $challan->driver_name,
                    $challan->user?->name ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function download(Challan $challan)
    {
        $pdfPath = $challan->pdf_file_path;

        if (! $pdfPath || ! Storage::disk('local')->exists($pdfPath)) {
            abort(404);
        }

        $fileName = 'challan-' . $challan->id . '-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($challan->challan_no ?? 'challan')) . '.pdf';

        return Storage::disk('local')->download($pdfPath, $fileName);
    }

    private function filteredQuery(array $filters)
    {
        return Challan::query()
            ->when($filters['from_date'] ?? null, fn ($query, $date) => $query->whereDate('challan_date', '>=', Carbon::parse($date)->toDateString()))
            ->when($filters['to_date'] ?? null, fn ($query, $date) => $query->whereDate('challan_date', '<=', Carbon::parse($date)->toDateString()))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['location'] ?? null, fn ($query, $location) => $query->where('location', $location))
            ->when($filters['vehicle_no'] ?? null, fn ($query, $vehicleNo) => $query->where('vehicle_no', $vehicleNo))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('challan_no', 'like', '%' . $search . '%')
                        ->orWhere('party_name', 'like', '%' . $search . '%')
                        ->orWhere('material_machine', 'like', '%' . $search . '%')
                        ->orWhere('receiver_name', 'like', '%' . $search . '%')
                        ->orWhere('driver_name', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('challan_date')
            ->orderByDesc('id');
    }
}
