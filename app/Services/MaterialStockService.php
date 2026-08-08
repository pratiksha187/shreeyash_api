<?php

namespace App\Services;

use App\Models\MaterialStock;
use App\Models\StockMovement;
use App\Support\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MaterialStockService
{
    public function availableQuantity(int $materialId, ?int $siteId = null): float
    {
        return (float) MaterialStock::query()
            ->forCurrentCompany()
            ->where('material_id', $materialId)
            ->where('labour_site_id', $siteId)
            ->value('available_quantity');
    }

    public function addStock(
        int $materialId,
        ?int $siteId,
        float $quantity,
        string $type = StockMovement::PURCHASE_IN,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $remarks = null,
        ?int $projectId = null,
        ?int $projectTaskId = null
    ): MaterialStock {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock quantity must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($materialId, $siteId, $quantity, $type, $referenceType, $referenceId, $remarks, $projectId, $projectTaskId) {
            $stock = $this->stockRow($materialId, $siteId);
            $stock->available_quantity = (float) $stock->available_quantity + $quantity;
            $stock->save();

            $this->movement($materialId, $siteId, $type, $quantity, (float) $stock->available_quantity, $referenceType, $referenceId, $remarks, $projectId, $projectTaskId);

            return $stock;
        });
    }

    public function removeStock(
        int $materialId,
        ?int $siteId,
        float $quantity,
        string $type = StockMovement::ISSUE_OUT,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $remarks = null,
        ?int $projectId = null,
        ?int $projectTaskId = null
    ): MaterialStock {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Issue quantity must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($materialId, $siteId, $quantity, $type, $referenceType, $referenceId, $remarks, $projectId, $projectTaskId) {
            $stock = $this->stockRow($materialId, $siteId);

            if ((float) $stock->available_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'issued_quantity' => 'Requested issue quantity is more than available stock.',
                ]);
            }

            $stock->available_quantity = (float) $stock->available_quantity - $quantity;
            $stock->save();

            $this->movement($materialId, $siteId, $type, $quantity, (float) $stock->available_quantity, $referenceType, $referenceId, $remarks, $projectId, $projectTaskId);

            return $stock;
        });
    }

    private function stockRow(int $materialId, ?int $siteId): MaterialStock
    {
        $companyId = app(Tenant::class)->id();

        $stock = MaterialStock::query()
            ->where('company_id', $companyId)
            ->where('material_id', $materialId)
            ->where('labour_site_id', $siteId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        return MaterialStock::query()->create([
            'company_id' => $companyId,
            'material_id' => $materialId,
            'labour_site_id' => $siteId,
            'available_quantity' => 0,
        ]);
    }

    private function movement(
        int $materialId,
        ?int $siteId,
        string $type,
        float $quantity,
        float $balanceAfter,
        ?string $referenceType,
        ?int $referenceId,
        ?string $remarks,
        ?int $projectId,
        ?int $projectTaskId
    ): void {
        $this->ensureStockMovementProjectColumns();

        StockMovement::query()->create([
            'material_id' => $materialId,
            'labour_site_id' => $siteId,
            'project_id' => $projectId,
            'project_task_id' => $projectTaskId,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'remarks' => $remarks,
        ]);
    }

    private function ensureStockMovementProjectColumns(): void
    {
        $connection = app(Tenant::class)->connectionName() ?: config('database.default');
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('stock_movements')) {
            return;
        }

        if (! $schema->hasColumn('stock_movements', 'project_id')) {
            $schema->table('stock_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('labour_site_id');
            });
        }

        if (! $schema->hasColumn('stock_movements', 'project_task_id')) {
            $schema->table('stock_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('project_task_id')->nullable()->after('project_id');
            });
        }
    }
}
