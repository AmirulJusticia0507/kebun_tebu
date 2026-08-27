<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Kebakaran Tebu',
                'icon_marker' => 'fire.png',
                'color_code'  => '#ef4444',
                'sla_hours'   => 2,
                'checklist_template' => [
                    ['label' => 'Api masih menyala?', 'type' => 'boolean'],
                    ['label' => 'Estimasi luas terbakar (hektar)', 'type' => 'number'],
                    ['label' => 'Sudah menghubungi pemadam kebakaran?', 'type' => 'boolean'],
                    ['label' => 'Korban jiwa?', 'type' => 'boolean'],
                ],
            ],
            [
                'name'        => 'Serangan Hama',
                'icon_marker' => 'pest.png',
                'color_code'  => '#f59e0b',
                'sla_hours'   => 24,
                'checklist_template' => [
                    ['label' => 'Jenis hama yang menyerang', 'type' => 'text'],
                    ['label' => 'Estimasi luas terserang (hektar)', 'type' => 'number'],
                    ['label' => 'Tingkat kerusakan (1-5)', 'type' => 'number'],
                ],
            ],
            [
                'name'        => 'Penyakit Tanaman',
                'icon_marker' => 'disease.png',
                'color_code'  => '#8b5cf6',
                'sla_hours'   => 48,
                'checklist_template' => [
                    ['label' => 'Gejala yang terlihat', 'type' => 'text'],
                    ['label' => 'Estimasi tanaman terkena (%)', 'type' => 'number'],
                    ['label' => 'Sudah dilakukan penyemprotan?', 'type' => 'boolean'],
                ],
            ],
            [
                'name'        => 'Banjir / Genangan',
                'icon_marker' => 'flood.png',
                'color_code'  => '#3b82f6',
                'sla_hours'   => 12,
                'checklist_template' => [
                    ['label' => 'Ketinggian air (cm)', 'type' => 'number'],
                    ['label' => 'Luas area tergenang (hektar)', 'type' => 'number'],
                    ['label' => 'Saluran drainase tersumbat?', 'type' => 'boolean'],
                ],
            ],
            [
                'name'        => 'Kendala Lainnya',
                'icon_marker' => 'warning.png',
                'color_code'  => '#6b7280',
                'sla_hours'   => 72,
                'checklist_template' => [],
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name']],
                $cat
            );
        }
    }
}
