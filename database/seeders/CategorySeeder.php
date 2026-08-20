<?php

namespace Database\Seeders;

use App\Domain\Listings\Models\Category;
use App\Support\Enums\ItemType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Mirrors the V1-V4 category roadmap from the master product doc.
     * V1 Fashion ships active; V2-V4 trees are seeded now but inactive
     * until their launch phase, per the `is_active` toggle.
     */
    public function run(): void
    {
        $this->tree('Fashion & Clothing', ItemType::Fashion, true, [
            'Tops', 'Bottoms', 'Dresses & Gowns', 'Shoes & Footwear',
            'Bags & Accessories', 'Kids & Baby Clothing', 'Jackets & Outerwear',
            'Sportswear', 'Traditional & Ankara', 'Underwear & Lingerie', 'Bales & Bundles',
        ]);

        $this->tree('Baby & Kids', ItemType::BabyKids, false, [
            'Baby Furniture', 'Toys & Games', 'School Items & Bags',
        ]);

        $this->tree('Books & Education', ItemType::Books, false, [
            'Textbooks', 'Novels & Fiction', 'Educational Materials',
        ]);

        $this->tree('Electronics & Phones', ItemType::Electronics, false, [
            'Smartphones & Tablets', 'Laptops & Computers', 'TVs & Decoders',
            'Sound Systems', 'Cameras',
        ]);

        $this->tree('Small Appliances & Kitchen', ItemType::Appliances, false, [
            'Blenders, Microwaves & Kettles', 'Pots, Cutlery & Kitchen Items',
            'Fans, Irons & Small Gadgets',
        ]);

        $this->tree('Household & Furniture', ItemType::Household, false, [
            'Sofas & Chairs', 'Beds & Mattresses', 'Wardrobes & Shelves',
            'Dining Sets & Tables', 'Curtains & Rugs',
        ]);

        $this->tree('Large Appliances', ItemType::Appliances, false, [
            'Washing Machines & Dryers', 'Fridges & Freezers', 'Air Conditioners',
            'Generators & Inverters', 'Water Dispensers',
        ]);

        $this->tree('Sports & Fitness', ItemType::Sports, false, [
            'Exercise Equipment', 'Bicycles', 'Sports Gear',
        ]);
    }

    private function tree(string $name, ItemType $itemType, bool $isActive, array $children): void
    {
        $parent = Category::firstOrCreate(
            ['slug' => Str::slug($name)],
            [
                'item_type' => $itemType,
                'name' => $name,
                'is_active' => $isActive,
            ]
        );

        foreach ($children as $index => $childName) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name.'-'.$childName)],
                [
                    'parent_id' => $parent->id,
                    'item_type' => $itemType,
                    'name' => $childName,
                    'sort_order' => $index,
                    'is_active' => $isActive,
                ]
            );
        }
    }
}
