<?php

namespace Database\Seeders;

use App\Models\ProtectorStock;
use Illuminate\Database\Seeder;

class ProtectorStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $glassTypes = ProtectorStock::GLASS_TYPES;

        // Samsung models requested
        $samsungModels = [
            // S Series
            'S26 Ultra', 'S26+', 'S26', 
            'S25 Ultra', 'S25+', 'S25', 'S25 Edge', 'S25 FE', 
            'S24 Ultra', 'S24+', 'S24', 'S24 FE', 
            'S23 Ultra', 'S23+', 'S23', 'S23 FE', 
            'S22 Ultra', 'S22+', 'S22', 
            'S21 Ultra', 'S21+', 'S21', 'S21 FE', 
            'S20 Ultra', 'S20+', 'S20', 'S20 FE', 
            'S10+', 'S10', 'S10e', 
            'Note20 Ultra', 'Note20', 'Note10+', 'Note10',
            // Z Series
            'Z Fold8 Ultra', 'Z Fold8', 'Z Flip8', 
            'Z Fold7', 'Z Flip7', 'Z Flip7 FE', 
            'Z Fold6', 'Z Flip6', 
            'Z Fold5', 'Z Flip5', 
            'Z Fold4', 'Z Flip4', 
            'Z Fold3', 'Z Flip3', 
            'Z Fold2', 'Z Flip',
            // A Series
            'A57', 'A37', 'A17', 
            'A56', 'A36', 'A16', 
            'A55', 'A35', 'A15', 
            'A54', 'A34', 'A14', 
            'A53', 'A33', 'A13', 
            'A52', 'A72'
        ];

        // Apple models requested
        $appleModels = [
            '17 Pro Max', '17 Pro', '17 Air', '17', '17e',
            '16e', '16 Pro Max', '16 Pro', '16 Plus', '16',
            '15 Pro Max', '15 Pro', '15 Plus', '15',
            '14 Pro Max', '14 Pro', '14 Plus', '14',
            'SE (3rd Gen)', 'SE (2nd Gen)',
            '13 Pro Max', '13 Pro', '13', '13 mini',
            '12 Pro Max', '12 Pro', '12', '12 mini',
            '11 Pro Max', '11 Pro', '11',
            'XS Max', 'XS', 'XR', 'X'
        ];

        // Seed Samsung Models
        foreach ($samsungModels as $index => $model) {
            $glassType = $glassTypes[$index % count($glassTypes)];
            ProtectorStock::updateOrCreate(
                [
                    'brand' => 'Samsung',
                    'model' => $model,
                    'glass_type' => $glassType,
                ],
                [
                    'stock_qty' => rand(2, 20),
                    'min_threshold' => 3,
                    'bin_location' => 'SAM-' . sprintf('%02d', ($index % 20) + 1),
                ]
            );
        }

        // Seed Apple Models
        foreach ($appleModels as $index => $model) {
            $glassType = $glassTypes[$index % count($glassTypes)];
            $fullModel = (str_contains($model, 'SE') || str_contains($model, 'X')) ? $model : 'iPhone ' . $model;
            ProtectorStock::updateOrCreate(
                [
                    'brand' => 'Apple',
                    'model' => $fullModel,
                    'glass_type' => $glassType,
                ],
                [
                    'stock_qty' => rand(2, 25),
                    'min_threshold' => 3,
                    'bin_location' => 'APP-' . sprintf('%02d', ($index % 20) + 1),
                ]
            );
        }
    }
}
