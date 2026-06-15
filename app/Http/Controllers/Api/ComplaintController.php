<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(Complaint::STATUSES)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $complaints = Complaint::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->limit($filters['limit'] ?? 30)
            ->get()
            ->map(fn (Complaint $complaint) => $this->complaintPayload($complaint));

        return response()->json([
            'message' => 'Complaints fetched successfully.',
            'complaints' => $complaints,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeInput($request);

        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
        ]);

        $complaint = Complaint::query()->create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
        ]);

        $complaint->load('user:id,name,mobile,designation');

        return response()->json([
            'message' => 'Complaint submitted successfully.',
            'complaint' => $this->complaintPayload($complaint),
        ], 201);
    }

    public function show(Request $request, int $complaint): JsonResponse
    {
        $complaint = Complaint::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->findOrFail($complaint);

        $complaint->load('user:id,name,mobile,designation');

        return response()->json([
            'message' => 'Complaint fetched successfully.',
            'complaint' => $this->complaintPayload($complaint),
        ]);
    }

    private function normalizeInput(Request $request): void
    {
        $data = [];

        if (! $request->has('message')) {
            foreach (['complaint', 'complaint_text', 'description', 'details'] as $key) {
                if ($request->has($key)) {
                    $data['message'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('subject')) {
            foreach (['title', 'complaint_title'] as $key) {
                if ($request->has($key)) {
                    $data['subject'] = $request->input($key);
                    break;
                }
            }
        }

        if ($data) {
            $request->merge($data);
        }
    }

    private function complaintPayload(Complaint $complaint): array
    {
        $complaint->loadMissing('user:id,name,mobile,designation');

        return [
            'id' => $complaint->id,
            'subject' => $complaint->subject,
            'message' => $complaint->message,
            'category' => $complaint->category,
            'priority' => $complaint->priority,
            'status' => $complaint->status,
            'admin_note' => $complaint->admin_note,
            'resolved_at' => $complaint->resolved_at,
            'submitted_at' => $complaint->created_at,
            'updated_at' => $complaint->updated_at,
            'employee' => [
                'id' => $complaint->user?->id,
                'name' => $complaint->user?->name,
                'mobile' => $complaint->user?->mobile,
                'designation' => $complaint->user?->designation,
            ],
        ];
    }
}
