<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Plumbing' => [
                ['name' => 'Leak Repair',       'base_price' => 350],
                ['name' => 'Pipe Installation',  'base_price' => 800],
                ['name' => 'Drain Cleaning',     'base_price' => 400],
                ['name' => 'Geyser Repair',      'base_price' => 600],
                ['name' => 'Toilet Repair',      'base_price' => 300],
            ],
            'Electrical' => [
                ['name' => 'Wiring & Rewiring',     'base_price' => 500],
                ['name' => 'Light Installation',     'base_price' => 250],
                ['name' => 'DB Board Repair',        'base_price' => 900],
                ['name' => 'Power Outlet Install',   'base_price' => 300],
                ['name' => 'Generator Installation', 'base_price' => 3000],
            ],
            'Carpentry' => [
                ['name' => 'Door Installation',  'base_price' => 500],
                ['name' => 'Cabinet Making',     'base_price' => 2000],
                ['name' => 'Shelf Installation', 'base_price' => 350],
                ['name' => 'Roof Repair',        'base_price' => 1500],
                ['name' => 'Deck Building',      'base_price' => 5000],
            ],
            'Painting' => [
                ['name' => 'Interior Painting',  'base_price' => 1200],
                ['name' => 'Exterior Painting',  'base_price' => 2500],
                ['name' => 'Waterproofing',      'base_price' => 1800],
                ['name' => 'Plastering',         'base_price' => 800],
            ],
            'Cleaning' => [
                ['name' => 'Deep Cleaning',      'base_price' => 500],
                ['name' => 'Window Cleaning',    'base_price' => 300],
                ['name' => 'Carpet Cleaning',    'base_price' => 400],
                ['name' => 'Upholstery Cleaning','base_price' => 350],
            ],
            'Landscaping' => [
                ['name' => 'Garden Maintenance',  'base_price' => 400],
                ['name' => 'Tree Removal',        'base_price' => 1000],
                ['name' => 'Irrigation Install',  'base_price' => 1500],
                ['name' => 'Lawn Care',           'base_price' => 250],
            ],
            'Tiling' => [
                ['name' => 'Floor Tiling',       'base_price' => 800],
                ['name' => 'Wall Tiling',        'base_price' => 600],
                ['name' => 'Bathroom Tiling',    'base_price' => 1200],
                ['name' => 'Pool Tiling',        'base_price' => 2500],
            ],
            'Welding & Metalwork' => [
                ['name' => 'Gate Fabrication',   'base_price' => 2000],
                ['name' => 'Burglar Bar Install','base_price' => 800],
                ['name' => 'Steel Structure',    'base_price' => 5000],
                ['name' => 'Welding Repair',     'base_price' => 400],
            ],
            'HVAC' => [
                ['name' => 'AC Installation',    'base_price' => 3000],
                ['name' => 'AC Repair',          'base_price' => 800],
                ['name' => 'Ventilation Install','base_price' => 1500],
                ['name' => 'Heating Repair',     'base_price' => 600],
            ],
            'General Handyman' => [
                ['name' => 'Furniture Assembly', 'base_price' => 300],
                ['name' => 'TV Wall Mounting',   'base_price' => 250],
                ['name' => 'Minor Repairs',      'base_price' => 200],
                ['name' => 'Moving & Hauling',   'base_price' => 500],
            ],
        ];

        foreach ($categories as $catName => $subs) {
            $cat = Category::create([
                'name'      => $catName,
                'icon_url'  => null,
                'is_active' => true,
            ]);

            foreach ($subs as $sub) {
                Subcategory::create([
                    'category_id' => $cat->id,
                    'name'        => $sub['name'],
                    'base_price'  => $sub['base_price'],
                ]);
            }
        }
    }
}
