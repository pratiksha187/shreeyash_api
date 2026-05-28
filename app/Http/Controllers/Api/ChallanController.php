<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challan;
use App\Services\ChallanPdfService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChallanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Challan::query()
            ->where('user_id', $request->user()->id);

        if (isset($filters['from_date'])) {
            $query->whereDate('challan_date', '>=', Carbon::parse($filters['from_date'])->toDateString());
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('challan_date', '<=', Carbon::parse($filters['to_date'])->toDateString());
        }

        $challans = $query
            ->orderByDesc('challan_date')
            ->orderByDesc('id')
            ->limit($filters['limit'] ?? 30)
            ->get()
            ->map(fn (Challan $challan) => $this->challanPayload($challan));

        return response()->json([
            'message' => 'Challans fetched successfully.',
            'challans' => $challans,
        ]);
    }

    public function show(Request $request, Challan $challan): JsonResponse
    {
        if ($challan->user_id !== $request->user()->id) {
            abort(404);
        }

        return response()->json([
            'message' => 'Challan fetched successfully.',
            'challan' => $this->challanPayload($challan),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeInput($request);

        $data = $request->validate([
            'challan_no' => ['required', 'string', 'max:50', Rule::unique('challans', 'challan_no')],
            'challan_date' => ['required', 'date'],
            'party_name' => ['required', 'string', 'max:255'],
            'material_machine' => ['required', 'string', 'max:255'],
            'vehicle_no' => ['required', 'string', 'max:100'],
            'measurement' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'delivery_time' => ['required', 'string', 'max:100'],
            'receiver_name' => ['required', 'string', 'max:150'],
            'driver_name' => ['required', 'string', 'max:150'],
        ]);

        $challan = Challan::query()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'challan_date' => Carbon::parse($data['challan_date'])->toDateString(),
        ]);

        $this->generatePdfForChallan($challan);
        $challan->refresh();

        return response()->json([
            'message' => 'Challan saved successfully.',
            'challan' => $this->challanPayload($challan),
        ], 201);
    }

    public function pdf(Request $request, Challan $challan): Response
    {
        if ($challan->user_id !== $request->user()->id) {
            abort(404);
        }

        $this->generatePdfForChallan($challan);

        $pdf = app(ChallanPdfService::class)->build($challan);
        $fileName = 'challan-' . $challan->id . '-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $challan->challan_no ?: 'challan') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function pdfData(Request $request, Challan $challan): JsonResponse
    {
        if ($challan->user_id !== $request->user()->id) {
            abort(404);
        }

        $this->generatePdfForChallan($challan);

        $pdf = app(ChallanPdfService::class)->build($challan);
        $fileName = 'challan-' . $challan->id . '-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $challan->challan_no ?: 'challan') . '.pdf';

        return response()->json([
            'message' => 'Challan PDF fetched successfully.',
            'challan_id' => $challan->id,
            'file_name' => $fileName,
            'mime_type' => 'application/pdf',
            'pdf_base64' => base64_encode($pdf),
            'pdf_url' => route('api.challans.pdf', $challan),
            'pdf_path' => '/api/challans/' . $challan->id . '/pdf',
            'pdf_file_path' => $challan->pdf_file_path,
        ]);
    }

    private function normalizeInput(Request $request): void
    {
        $aliases = [
            'challan_no' => ['challanNo', 'challan_number', 'challanNumber', 'number'],
            'challan_date' => ['date', 'challanDate', 'challan_date'],
            'party_name' => ['name_of_party', 'partyName', 'party_name', 'nameOfParty'],
            'material_machine' => ['material_mc', 'material_m_c', 'materialMachine', 'material_machine', 'material'],
            'vehicle_no' => ['vehicleNo', 'vehicle_number', 'vehicleNumber', 'vehicle_no'],
            'delivery_time' => ['time', 'deliveryTime', 'delivery_time'],
            'receiver_name' => ['receiverName', 'receiver_name', 'receiver'],
            'driver_name' => ['driverName', 'driver_name', 'driver'],
        ];

        $data = [];

        foreach ($aliases as $target => $sourceKeys) {
            if ($request->filled($target)) {
                continue;
            }

            foreach ($sourceKeys as $sourceKey) {
                if ($request->filled($sourceKey)) {
                    $data[$target] = $request->input($sourceKey);
                    break;
                }
            }
        }

        foreach (['measurement', 'location'] as $key) {
            if ($request->filled($key)) {
                $data[$key] = $request->input($key);
            }
        }

        if (isset($data['challan_date'])) {
            $data['challan_date'] = $this->parseDate($data['challan_date']);
        }

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        if ($data) {
            $request->merge($data);
        }

        if ($request->filled('challan_date')) {
            $request->merge([
                'challan_date' => $this->parseDate((string) $request->input('challan_date')),
            ]);
        }
    }

    private function generatePdfForChallan(Challan $challan): void
    {
        $challan->loadMissing('user');

        $pdf = app(ChallanPdfService::class)->build($challan);
        $safeChallanNo = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($challan->challan_no ?? 'challan'));
        $safeChallanNo = trim($safeChallanNo, '-');
        $fileName = 'challan-' . $challan->id . '-' . ($safeChallanNo !== '' ? $safeChallanNo : 'challan') . '.pdf';
        $relativePath = 'challans/' . $challan->user_id . '/' . $fileName;

        Storage::disk('local')->makeDirectory(dirname($relativePath));
        Storage::disk('local')->put($relativePath, $pdf);
        $challan->update(['pdf_file_path' => $relativePath]);
        $challan->refresh();
    }

    private function parseDate(string $date): string
    {
        $date = trim($date);
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y'];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $date);

                if ($parsed !== false && $parsed->format($format) === $date) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'challan_date' => 'The challan date must be a valid date.',
            ]);
        }
    }

    private function challanPayload(Challan $challan): array
    {
        $challan->loadMissing('user:id,name,mobile,designation');

        return [
            'id' => $challan->id,
            'challan_no' => $challan->challan_no,
            'challan_date' => $challan->challan_date?->toDateString(),
            'date_display' => $challan->challan_date?->format('d/m/Y'),
            'party_name' => $challan->party_name,
            'material_machine' => $challan->material_machine,
            'vehicle_no' => $challan->vehicle_no,
            'measurement' => $challan->measurement,
            'location' => $challan->location,
            'delivery_time' => $challan->delivery_time,
            'time' => $challan->delivery_time,
            'receiver_name' => $challan->receiver_name,
            'driver_name' => $challan->driver_name,
            'pdf_file_path' => $challan->pdf_file_path,
            'pdf_url' => $challan->pdf_file_path ? route('api.challans.pdf', $challan) : null,
            'pdf_path' => $challan->pdf_file_path ? '/api/challans/' . $challan->id . '/pdf' : null,
            'submitted_by' => [
                'id' => $challan->user?->id,
                'name' => $challan->user?->name,
                'mobile' => $challan->user?->mobile,
                'designation' => $challan->user?->designation,
            ],
            'created_at' => $challan->created_at,
            'updated_at' => $challan->updated_at,
        ];
    }
}
