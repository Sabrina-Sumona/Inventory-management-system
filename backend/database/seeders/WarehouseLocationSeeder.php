<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Seeder;

class WarehouseLocationSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'MAIN-WAREHOUSE')
            ->firstOrFail();

        $baseData = [
            'company_id' => $warehouse->company_id,
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'is_active' => true,
        ];

        $zone = WarehouseLocation::updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'code' => 'ZONE-A',
            ],
            [
                ...$baseData,
                'parent_id' => null,
                'name' => 'Main Storage Zone',
                'type' => 'zone',
                'barcode' => null,
                'capacity' => null,
                'description' => 'Primary storage zone for Desh Solar inventory.',
            ]
        );

        $rack = WarehouseLocation::updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'code' => 'RACK-A1',
            ],
            [
                ...$baseData,
                'parent_id' => $zone->id,
                'name' => 'Solar Panel Rack A1',
                'type' => 'rack',
                'barcode' => null,
                'capacity' => null,
                'description' => 'Rack for storing solar panels and related equipment.',
            ]
        );

        $shelf = WarehouseLocation::updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'code' => 'SHELF-A1-01',
            ],
            [
                ...$baseData,
                'parent_id' => $rack->id,
                'name' => 'Shelf A1-01',
                'type' => 'shelf',
                'barcode' => null,
                'capacity' => null,
                'description' => null,
            ]
        );

        WarehouseLocation::updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'code' => 'BIN-A1-01-01',
            ],
            [
                ...$baseData,
                'parent_id' => $shelf->id,
                'name' => 'Bin A1-01-01',
                'type' => 'bin',
                'barcode' => 'DS-BIN-A1-01-01',
                'capacity' => null,
                'description' => null,
            ]
        );
    }
}