<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\Material;
use App\Models\MaterialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialRequestController extends Controller
{
    public function materials(): JsonResponse
    {
        $materials = Material::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Material $material) => $this->materialPayload($material));

        return response()->json([
            'message' => 'Materials fetched successfully.',
            'materials' => $materials,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $requests = MaterialRequest::query()
            ->forCurrentCompany()
            ->with(['material', 'site'])
            ->where('user_id', $request->user()->id)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn (MaterialRequest $materialRequest) => $this->requestPayload($materialRequest));

        return response()->json([
            'message' => 'Material requests fetched successfully.',
            'material_requests' => $requests,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'labour_site_id' => ['nullable', 'exists:labour_sites,id'],
            'material_id' => ['required', 'exists:materials,id'],
            'requested_quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'required_date' => ['nullable', 'date'],
            'purpose' => ['nullable', 'string', 'max:2000'],
        ]);

        $materialRequest = MaterialRequest::query()->create([
            'user_id' => $request->user()->id,
            'labour_site_id' => $data['labour_site_id'] ?? null,
            'material_id' => $data['material_id'],
            'requested_quantity' => $data['requested_quantity'],
            'required_date' => $data['required_date'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'status' => 'pending',
        ]);

        $materialRequest->load(['material', 'site']);

        return response()->json([
            'message' => 'Material request submitted successfully.',
            'material_request' => $this->requestPayload($materialRequest),
        ], 201);
    }

    public function show(Request $request, int $materialRequest): JsonResponse
    {
        $materialRequest = MaterialRequest::query()
            ->forCurrentCompany()
            ->with(['material', 'site', 'issues'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($materialRequest);

        return response()->json([
            'message' => 'Material request fetched successfully.',
            'material_request' => $this->requestPayload($materialRequest),
        ]);
    }

    public function sites(): JsonResponse
    {
        return response()->json([
            'message' => 'Sites fetched successfully.',
            'sites' => LabourSite::query()
                ->forCurrentCompany()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'address']),
        ]);
    }

    private function materialPayload(Material $material): array
    {
        return [
            'id' => $material->id,
            'name' => $material->name,
            'material_type' => $material->material_type,
            'unit' => $material->unit,
            'minimum_stock' => $material->minimum_stock,
        ];
    }

    private function requestPayload(MaterialRequest $materialRequest): array
    {
        return [
            'id' => $materialRequest->id,
            'site' => $materialRequest->site ? [
                'id' => $materialRequest->site->id,
                'name' => $materialRequest->site->name,
            ] : null,
            'material' => $materialRequest->material ? $this->materialPayload($materialRequest->material) : null,
            'requested_quantity' => $materialRequest->requested_quantity,
            'approved_quantity' => $materialRequest->approved_quantity,
            'issued_quantity' => $materialRequest->issued_quantity,
            'required_date' => $materialRequest->required_date?->toDateString(),
            'purpose' => $materialRequest->purpose,
            'status' => $materialRequest->status,
            'admin_note' => $materialRequest->admin_note,
            'submitted_at' => $materialRequest->created_at,
            'updated_at' => $materialRequest->updated_at,
        ];
    }
}
