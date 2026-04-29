<?php

namespace Database\Seeders;

use App\Models\MenuItemCategory;
use App\Models\ProviderMenuItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProviderMenuItemSeeder extends Seeder
{
    /**
     * Seed provider menu items for seeded providers.
     *
     * category_id values match MenuItemCategorySeeder insertion order on a fresh DB
     * (Restaurant 1–19, Catering 20–32, Bakery 33–48, Grocery 49–73, Food truck 74–84, Other 85–88; Bundles = 87).
     */
    public function run(): void
    {
        // Copy seed images from public/images/seed/ (git-tracked) into storage/app/public/seed/
        // so every team member gets the images automatically when they run db:seed.
        $seedImagesSource = public_path('images/seed');
        $seedImagesDest = storage_path('app/public/seed');

        if (File::isDirectory($seedImagesSource)) {
            File::ensureDirectoryExists($seedImagesDest);
            File::copyDirectory($seedImagesSource, $seedImagesDest);
            $this->command->info('Seed images copied to storage.');
        } else {
            $this->command->warn('Seed images folder not found at public/images/seed — skipping image copy.');
        }

        $providerEmails = [
            'provider@nubl.com',
            'community@nubl.com',
            'panda@nubl.com',
            'danube@nubl.com',
            'mcdonalds@nubl.com',
            'carrefour@nubl.com',
            'shawarmahouse@nubl.com',
        ];
        $providers = User::whereIn('email', $providerEmails)->where('membership_type', User::MEMBERSHIP_PROVIDER)->get();

        if ($providers->isEmpty()) {
            $this->command->warn('No providers found. Run ProviderSeeder first.');

            return;
        }

        $menuItems = [
            [
                'provider_email' => 'provider@nubl.com',
                // Restaurant: 4 Burgers, 3 Soups, 16 Desserts.
                'items' => [
                    ['name' => 'Family meal package', 'description' => 'Complete meal for 4-6 people', 'price' => 85.00, 'category_id' => 4, 'max_per_request' => 2],
                    ['name' => 'Daily support order', 'description' => 'Single daily meal support', 'price' => 25.00, 'category_id' => 4, 'max_per_request' => 5],
                    ['name' => 'Rice & Chicken Combo', 'description' => 'Traditional rice with grilled chicken', 'price' => 35.00, 'category_id' => 4, 'max_per_request' => 3],
                    ['name' => 'Vegetable Soup', 'description' => 'Fresh vegetable soup', 'price' => 15.00, 'category_id' => 3, 'max_per_request' => 4],
                    ['name' => 'Fresh Bread Basket', 'description' => 'Assorted fresh bread', 'price' => 12.00, 'category_id' => 16, 'max_per_request' => 2],
                ],
            ],
            [
                'provider_email' => 'community@nubl.com',
                // Catering: 32 Lunch Boxes, 29 Sandwich Platters.
                'items' => [
                    ['name' => 'Weekly assistance', 'description' => 'Weekly meal package', 'price' => 120.00, 'category_id' => 32, 'max_per_request' => 1],
                    ['name' => 'Lunch Box', 'description' => 'Single lunch box', 'price' => 20.00, 'category_id' => 32, 'max_per_request' => 5],
                    ['name' => 'Breakfast Pack', 'description' => 'Morning breakfast essentials', 'price' => 18.00, 'category_id' => 32, 'max_per_request' => 3],
                    ['name' => 'Pastry Set', 'description' => 'Assorted pastries', 'price' => 22.00, 'category_id' => 29, 'max_per_request' => 2],
                ],
            ],
            [
                'provider_email' => 'panda@nubl.com',
                // Grocery: 59 Pasta & Rice, 62 Sweets & Chocolate, 68 Oils & Ghee.
                'items' => [
                    // Pasta & Rice (59)
                    ['name' => 'Abukass Basmati Rice 10Kg', 'description' => 'Premium sella basmati rice with long, fluffy grains and a rich aroma, perfect for traditional dishes', 'price' => 94.99, 'category_id' => 59, 'max_per_request' => 10, 'sku' => '132855', 'image_path' => 'seed/panda/Abukass Basmati Rice 10Kg.jpg'],
                    ['name' => 'Abukass Sella Basmati Rice 5Kg', 'description' => 'Abukass sella basmati rice, 5kg.', 'price' => 48.5, 'category_id' => 59, 'max_per_request' => 10, 'sku' => '132845', 'image_path' => 'seed/panda/Abukass Sella Basmati Rice 5Kg.jpg'],
                    ['name' => 'Al Walimah Sella Basmati Rice 10kg', 'description' => 'Premium sella basmati rice for traditional dishes.', 'price' => 59.99, 'category_id' => 59, 'max_per_request' => 10, 'sku' => '132665', 'image_path' => 'seed/panda/Al Walimah Sella Basmati Rice 10kg.jpg'],
                    ['name' => 'Sunwhite Calrose Rice 10kg', 'description' => 'Calrose rice 10kg premium quality.', 'price' => 84.99, 'category_id' => 59, 'max_per_request' => 10, 'sku' => '133160', 'image_path' => 'seed/panda/Sunwhite Calrose Rice 10kg.jpg'],
                    ['name' => 'Channa Dal 800g', 'description' => 'Features the finest quality whole grains Hygienically packed for safe consumption Packed with nutrients to ensure good health Polished well to ensure quality and freshness Ideal for use in a variety of dishes', 'price' => 15.99, 'category_id' => 59, 'max_per_request' => 8, 'sku' => '100156510', 'image_path' => 'seed/panda/Channa Dal 800g.jpg'],
                    // Sweets & Chocolate (62)
                    ['name' => 'Al Osra Fine Sugar Bag 5kg', 'description' => 'Fine sugar bag perfect for everyday sweetening and cooking.', 'price' => 16.99, 'category_id' => 62, 'max_per_request' => 10, 'sku' => '1129040', 'image_path' => 'seed/panda/Al Osra Fine Sugar Bag 5kg.jpg'],
                    ['name' => 'Panda Sugar 10 Kg', 'description' => 'Packaged white sugar from Panda.', 'price' => 29.99, 'category_id' => 62, 'max_per_request' => 10, 'sku' => '187900665', 'image_path' => 'seed/panda/Panda Sugar 10 Kg.jpg'],
                    ['name' => 'SIS Brown Sugar 1kg', 'description' => 'Brown sugar 1kg rich and sweet flavor.', 'price' => 19.99, 'category_id' => 62, 'max_per_request' => 10, 'sku' => '3425635', 'image_path' => 'seed/panda/SIS Brown Sugar 1kg.jpg'],
                    ['name' => 'Steviana Low Calorie Sweetener 125G', 'description' => 'Steviana low calorie sweetener, 125g.', 'price' => 9.99, 'category_id' => 62, 'max_per_request' => 25, 'sku' => '2450185', 'image_path' => 'seed/panda/Steviana Low Calorie Sweetener 125G.jpg'],
                    ['name' => 'Al Osra Sugar Fine 2Kg', 'description' => 'Al Osra fine sugar, 2kg.', 'price' => 12.99, 'category_id' => 62, 'max_per_request' => 10, 'sku' => '1129075', 'image_path' => 'seed/panda/Al Osra Sugar Fine 2Kg.jpg'],
                    // Oils & Ghee (68)
                    ['name' => 'Afia Corn Oil (2x1.5L + 2x500ml) 4L', 'description' => 'Afia corn oil 4L pack, a healthy and light choice for everyday cooking and frying.', 'price' => 42.99, 'category_id' => 68, 'max_per_request' => 8, 'sku' => '100246242', 'image_path' => 'seed/panda/Afia Corn Oil (2x1.5L + 2x500ml) 4L.jpg'],
                    ['name' => 'Miza Sunflower Oil 2X1.5L', 'description' => 'Miza Sunflower Oil 2X1.5L', 'price' => 26.99, 'category_id' => 68, 'max_per_request' => 8, 'sku' => '182300708', 'image_path' => 'seed/panda/Miza Sunflower Oil 2X1.5L.jpg'],
                    ['name' => 'Culina Vegetable Ghee 1kg', 'description' => 'Culina Vegetable Ghee 1kg', 'price' => 16.99, 'category_id' => 68, 'max_per_request' => 8, 'sku' => '100192195', 'image_path' => 'seed/panda/Culina Vegetable Ghee 1kg.jpg'],
                    ['name' => 'Baya Organic Olive Oil 500ml', 'description' => 'Baya Organic Olive Oil 500ml', 'price' => 35.99, 'category_id' => 68, 'max_per_request' => 8, 'sku' => '202252345', 'image_path' => 'seed/panda/Baya Organic Olive Oil 500ml.jpg'],
                    ['name' => 'Gold Branch Pomace Olive Oil 1L', 'description' => 'Gold Branch Pomace Olive Oil 1L', 'price' => 48.99, 'category_id' => 68, 'max_per_request' => 8, 'sku' => '201701858', 'image_path' => 'seed/panda/Gold Branch Pomace Olive Oil 1L.jpg'],
                ],
            ],
            [
                'provider_email' => 'danube@nubl.com',
                // Grocery: 72 Personal Care, 71 Cleaning, 70 Baby Products.
                'items' => [
                    // Hair — Personal Care (72)
                    ['name' => 'Sunsilk Conditioner Black Shine 350ml', 'description' => 'Conditioner for long-lasting black shine.', 'price' => 20.00, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '8738', 'image_path' => 'seed/danube/Sunsilk Conditioner Black Shine 350ml.jpg'],
                    ['name' => 'Dove Shampoo Amino Acid Intensive Repair 400ml', 'description' => 'Intensive repair shampoo with amino acids.', 'price' => 25.95, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '6321', 'image_path' => 'seed/danube/Dove Shampoo Amino Acid Intensive Repair 400ml.jpg'],
                    ['name' => 'Clear Men\'s Anti Dandruff Shampoo Hairfall Defense 400ml', 'description' => 'Anti-dandruff shampoo — hair fall defense.', 'price' => 24.95, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '6301', 'image_path' => 'seed/danube/Clear Men\'s Anti Dandruff Shampoo Hairfall Defense 400ml.jpg'],
                    ['name' => 'Dove Women Invisible Dry Deodorant 150ml', 'description' => 'Invisible dry anti-perspirant deodorant.', 'price' => 31.50, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '8701', 'image_path' => 'seed/danube/Dove Women Invisible Dry Deodorant 150ml.jpg'],
                    ['name' => 'Axe Black Night Deodorant Spray 150ml', 'description' => 'Deodorant body spray — Black Night.', 'price' => 29.95, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '6525', 'image_path' => 'seed/danube/Axe Black Night Deodorant Spray 150ml.png'],
                    ['name' => 'Rexona Men Antiperspirant Deodorant Stick 48 Hour Sweat & Odor Protection Xtra Cool with Motionsense Technology 40g', 'description' => '48h antiperspirant stick — Xtra Cool.', 'price' => 23.95, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '6524', 'image_path' => 'seed/danube/Rexona Men Antiperspirant Deodorant Stick 48 Hour Sweat & Odor Protection Xtra Cool with Motionsense Technology 40g.jpg'],
                    ['name' => 'Closeup Triple Fresh Gel Toothpaste For 12 Hours Fresh Breath Cool Breeze with Antibacterial Mouthwash & Microshine Crystals 120ml', 'description' => 'Triple fresh gel toothpaste — Cool Breeze.', 'price' => 11.25, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '6474', 'image_path' => 'seed/danube/Closeup Triple Fresh Gel Toothpaste For 12 Hours Fresh Breath Cool Breeze with Antibacterial Mouthwash & Microshine Crystals 120ml.jpg'],
                    ['name' => 'Signal Fluoride Toothpaste Cavity Fighter for 10x Stronger Teeth 120ml', 'description' => 'Fluoride toothpaste — cavity fighter.', 'price' => 8.75, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '58', 'image_path' => 'seed/danube/Signal Fluoride Toothpaste Cavity Fighter for 10x Stronger Teeth 120ml.jpg'],
                    ['name' => 'Closeup Triple Fresh Gel Toothpaste For 12 Hours Fresh Breath Menthol Fresh with Antibacterial Mouthwash & Microshine Crystals 120ml', 'description' => 'Triple fresh gel toothpaste — Menthol Fresh.', 'price' => 11.50, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '51', 'image_path' => 'seed/danube/Closeup Triple Fresh Gel Toothpaste For 12 Hours Fresh Breath Menthol Fresh with Antibacterial Mouthwash & Microshine Crystals 120ml.png'],
                    ['name' => 'Lux Soft Rose Body Wash 700ml', 'description' => 'Perfumed body wash — Soft Rose.', 'price' => 46.95, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '6428', 'image_path' => 'seed/danube/Lux Soft Rose Body Wash 700ml.jpg'],
                    ['name' => 'Dove Beauty Cream Soap Bar For All Skin Types Original with ¼ Moisturising Cream 160g', 'description' => 'Beauty cream soap bar with moisturising cream.', 'price' => 11.95, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '8697', 'image_path' => 'seed/danube/Dove Beauty Cream Soap Bar For All Skin Types Original with ¼ Moisturising Cream 160g.jpg'],
                    ['name' => 'Lifebuoy Antibacterial Body Wash Mild Care For 100% Stronger Germ Protection* & Hygiene 500ml', 'description' => 'Antibacterial body wash — mild care.', 'price' => 44.25, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '6371', 'image_path' => 'seed/danube/Lifebuoy Antibacterial Body Wash Mild Care For 100% Stronger Germ Protection & Hygiene 500ml.jpg'],
                    ['name' => 'Vaseline Intensive Care Body Lotion for Dry to Very Dry Skin Cocoa Radiant Fast-Absorbing 72hr Moisturising 400ml', 'description' => 'Intensive care body lotion — Cocoa Radiant.', 'price' => 31.25, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '6390', 'image_path' => 'seed/danube/Vaseline Intensive Care Body Lotion for Dry to Very Dry Skin Cocoa Radiant Fast-Absorbing 72hr Moisturising 400ml.jpg'],
                    ['name' => 'Fair & Lovely Multi-Vitamin Face Cream Pump 100g', 'description' => 'Multi-vitamin face cream.', 'price' => 34.50, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '6445', 'image_path' => 'seed/danube/Fair & Lovely Multi-Vitamin Face Cream Pump 100g.jpg'],
                    ['name' => 'Vaseline Lip Therapy Mint Lip Balm With Pure Jelly & Mint Extracts 4.8g', 'description' => 'Lip therapy — mint.', 'price' => 12.95, 'category_id' => 72, 'max_per_request' => 8, 'sku' => '56938', 'image_path' => 'seed/danube/Vaseline Lip Therapy Mint Lip Balm With Pure Jelly & Mint Extracts 4.8g.jpg'],
                    // Cleaning Supplies (71)
                    ['name' => 'Lifebuoy Antibacterial Hand Wash 100% Stronger Germ Protection 450ml', 'description' => 'Antibacterial liquid hand wash.', 'price' => 28.50, 'category_id' => 71, 'max_per_request' => 8, 'sku' => '58318', 'image_path' => 'seed/danube/Lifebuoy Antibacterial Hand Wash 100% Stronger Germ Protection 450ml.jpg'],
                    ['name' => 'Lifebuoy Antibacterial Hand Wash Mild Care 450ml', 'description' => 'Antibacterial hand wash — mild care.', 'price' => 28.50, 'category_id' => 71, 'max_per_request' => 8, 'sku' => '53839', 'image_path' => 'seed/danube/Lifebuoy Antibacterial Hand Wash Mild Care 450ml.jpeg'],
                    ['name' => 'Lifebuoy Antibacterial Hand Wash Cool Fresh 450ml', 'description' => 'Antibacterial hand wash — cool fresh.', 'price' => 28.50, 'category_id' => 71, 'max_per_request' => 8, 'sku' => '53838', 'image_path' => 'seed/danube/Lifebuoy Antibacterial Hand Wash Cool Fresh 450ml.jpeg'],
                    ['name' => 'Lux Perfumed Liquid Hand Wash For All Skin Types Soft Rose Glycerin Enriched Liquid Soap 500ml', 'description' => 'Perfumed liquid hand wash — Soft Rose.', 'price' => 25.95, 'category_id' => 71, 'max_per_request' => 8, 'sku' => '6468', 'image_path' => 'seed/danube/Lux Perfumed Liquid Hand Wash For All Skin Types Soft Rose Glycerin Enriched Liquid Soap 500ml.jpg'],
                    // Baby Products (70)
                    ['name' => 'Vaseline Baby Jelly 450ml', 'description' => 'Baby jelly — gentle skin protection.', 'price' => 39.50, 'category_id' => 70, 'max_per_request' => 8, 'sku' => '14881', 'image_path' => 'seed/danube/Vaseline Baby Jelly 450ml.jpg'],
                ],
            ],
            [
                'provider_email' => 'mcdonalds@nubl.com',
                // Restaurant: 2 Salads, 5 Sandwiches, 11 Chicken, 15 Sauces & Sides, 16 Desserts, 17 Hot Drinks, 18 Cold Drinks, 19 Fresh Juices, 87 Bundles.
                'items' => [
                    // Hot Drinks (17)
                    ['name' => 'Dilmah Black Tea', 'description' => 'Dilmah Black Tea', 'price' => 5.00, 'category_id' => 17, 'max_per_request' => 5, 'sku' => '545f7edb-3593-4247-b256-4655941e6998', 'image_path' => 'seed/mcdonalds/Dilmah Black Tea.png'],
                    // Cold Drinks (18)
                    ['name' => 'Large Sprite', 'description' => 'Large Sprite', 'price' => 9.00, 'category_id' => 18, 'max_per_request' => 6, 'sku' => 'b1396cf3-47bd-4dc8-bca5-fa32e228a35b', 'image_path' => 'seed/mcdonalds/Large Sprite.jpg'],
                    ['name' => 'Large Fanta Orange', 'description' => 'Large Fanta Orange', 'price' => 9.00, 'category_id' => 18, 'max_per_request' => 6, 'sku' => 'da28f997-20c8-4288-9107-dacbef6b8379', 'image_path' => 'seed/mcdonalds/Large Fanta Orange.jpg'],
                    // Fresh Juices (19)
                    ['name' => 'Large Orange Juice', 'description' => 'Large Orange Juice', 'price' => 13.00, 'category_id' => 19, 'max_per_request' => 5, 'sku' => '20ec317b-beaa-4ce3-91bc-98f4b2f78d78', 'image_path' => 'seed/mcdonalds/Large Orange Juice.jpg'],
                    // Cold Drinks (18)
                    ['name' => 'Medium Coca Cola', 'description' => 'Medium Coca Cola', 'price' => 8.00, 'category_id' => 18, 'max_per_request' => 6, 'sku' => '19f1426b-d2ec-45f9-9c4b-080912b9d01a', 'image_path' => 'seed/mcdonalds/Medium Coca Cola.jpg'],
                    ['name' => 'Large Coca Cola Zero', 'description' => 'Large Coca Cola Zero', 'price' => 9.00, 'category_id' => 18, 'max_per_request' => 6, 'sku' => '22319a02-2290-4ca9-82c4-03b344d7d910', 'image_path' => 'seed/mcdonalds/Large Coca Cola Zero.jpg'],
                    ['name' => 'Medium Sprite', 'description' => 'Medium Sprite', 'price' => 8.00, 'category_id' => 18, 'max_per_request' => 6, 'sku' => '17e04ddf-649f-4820-962f-2d34e8486773', 'image_path' => 'seed/mcdonalds/Medium Sprite.jpg'],
                    // Bundles (87)
                    ['name' => 'Large Big Mac Meal', 'description' => 'Large Big Mac Meal', 'price' => 28.00, 'category_id' => 87, 'max_per_request' => 4, 'sku' => 'c8986e94-eb2e-4fa0-97bf-e33668485234', 'image_path' => 'seed/mcdonalds/Large Big Mac Meal.png'],
                    ['name' => 'Large McChicken Meal', 'description' => 'Large McChicken Meal', 'price' => 28.00, 'category_id' => 87, 'max_per_request' => 4, 'sku' => '1c1164e2-026b-4bbd-b931-fdbd13078455', 'image_path' => 'seed/mcdonalds/Large McChicken Meal.png'],
                    ['name' => 'Large 9 Pcs Nuggets Meal', 'description' => 'Large 9 Pcs Nuggets Meal', 'price' => 28.00, 'category_id' => 87, 'max_per_request' => 4, 'sku' => '03c6ff0e-3d9c-4bfc-b7ab-98762f18bc87', 'image_path' => 'seed/mcdonalds/Large 9 Pcs Nuggets Meal.jpg'],
                    ['name' => 'Medium Big Mac Meal', 'description' => 'Medium Big Mac Meal', 'price' => 26.00, 'category_id' => 87, 'max_per_request' => 4, 'sku' => '3dd41ccc-5809-4eda-a1a9-484842af8e9c', 'image_path' => 'seed/mcdonalds/Medium Big Mac Meal.png'],
                    ['name' => 'Medium Chicken Mac Meal', 'description' => 'Medium Chicken Mac Meal', 'price' => 27.00, 'category_id' => 87, 'max_per_request' => 4, 'sku' => 'cb4b3e8d-d806-48cf-93d2-68341ca272c4', 'image_path' => 'seed/mcdonalds/Medium Chicken Mac Meal.jpg'],
                    ['name' => 'Large McArabia Meal', 'description' => 'Large McArabia Meal', 'price' => 30.00, 'category_id' => 87, 'max_per_request' => 4, 'sku' => '52190f94-c840-4ae7-a164-3eaedfbff6dc', 'image_path' => 'seed/mcdonalds/Large McArabia Meal.png'],
                    // Sandwiches (5)
                    ['name' => 'Big Mac', 'description' => 'Big Mac', 'price' => 18.00, 'category_id' => 5, 'max_per_request' => 5, 'sku' => '522c7542-8a68-4560-9340-a441e922b8c2', 'image_path' => 'seed/mcdonalds/Big Mac.png'],
                    ['name' => 'McChicken', 'description' => 'McChicken', 'price' => 18.00, 'category_id' => 5, 'max_per_request' => 5, 'sku' => '1e413dcc-8169-42e7-8f72-82dcbfce798a', 'image_path' => 'seed/mcdonalds/McChicken.png'],
                    ['name' => 'McRoyale', 'description' => 'McRoyale', 'price' => 20.00, 'category_id' => 5, 'max_per_request' => 5, 'sku' => '6be6cfef-166e-47c9-9961-d52ea2fe7cba', 'image_path' => 'seed/mcdonalds/McRoyale.jpg'],
                    ['name' => 'Quarter Pounder with Cheese', 'description' => 'Quarter Pounder with Cheese', 'price' => 19.00, 'category_id' => 5, 'max_per_request' => 5, 'sku' => '318a0895-0e1a-4637-8cb0-162e93f11b75', 'image_path' => 'seed/mcdonalds/Quarter Pounder with Cheese.png'],
                    // Chicken (11)
                    ['name' => '6 Pcs McNuggets', 'description' => '6 Pcs McNuggets', 'price' => 11.00, 'category_id' => 11, 'max_per_request' => 5, 'sku' => '7a929bd5-80ad-4455-8a59-360b8acceb39', 'image_path' => 'seed/mcdonalds/6 Pcs McNuggets.jpg'],
                    // Sauces & Sides (15)
                    ['name' => 'Large French Fries', 'description' => 'Large French Fries', 'price' => 11.00, 'category_id' => 15, 'max_per_request' => 5, 'sku' => 'a95c19dd-33a6-457a-8d86-d0ca59442241', 'image_path' => 'seed/mcdonalds/Large French Fries.jpg'],
                    ['name' => 'McWings 3 pcs', 'description' => 'McWings 3 pcs', 'price' => 10.00, 'category_id' => 11, 'max_per_request' => 5, 'sku' => '572deb1a-6d34-4437-b211-1112abf5ec5b', 'image_path' => 'seed/mcdonalds/McWings 3 pcs.jpg'],
                    // Salads (2)
                    ['name' => 'Chicken Caesar Salad', 'description' => 'Chicken Caesar Salad', 'price' => 24.00, 'category_id' => 2, 'max_per_request' => 3, 'sku' => '6d8dcb2c-33e7-4e8e-af5a-8dcd03c182f3', 'image_path' => 'seed/mcdonalds/Chicken Caesar Salad.jpg'],
                    ['name' => 'Spicy McNuggets 6 Pcs', 'description' => 'Spicy McNuggets 6 Pcs', 'price' => 11.00, 'category_id' => 11, 'max_per_request' => 5, 'sku' => 'b761dfdb-d518-4afc-b5e0-680c88eb4a08', 'image_path' => 'seed/mcdonalds/Spicy McNuggets 6 Pcs.jpg'],
                    // Desserts (16)
                    ['name' => 'Boston Donuts', 'description' => 'Boston Donuts', 'price' => 7.00, 'category_id' => 16, 'max_per_request' => 5, 'sku' => 'b8abeaa7-5761-483d-886e-994a0e858ce4', 'image_path' => 'seed/mcdonalds/Boston Donuts.jpg'],
                    ['name' => 'Cheese Croissant', 'description' => 'Cheese Croissant', 'price' => 10.00, 'category_id' => 16, 'max_per_request' => 5, 'sku' => '2d07c99f-5d8f-4178-aedd-ac8ad58a4150', 'image_path' => 'seed/mcdonalds/Cheese Croissant.jpg'],
                    ['name' => 'Chocolate Marble Cake', 'description' => 'Chocolate Marble Cake', 'price' => 11.00, 'category_id' => 16, 'max_per_request' => 4, 'sku' => '329a7bd9-a1be-4da6-a338-8cacf0908acd', 'image_path' => 'seed/mcdonalds/Chocolate Marble Cake.jpg'],
                    ['name' => 'Choco Mix', 'description' => 'Choco Mix', 'price' => 7.00, 'category_id' => 16, 'max_per_request' => 5, 'sku' => '614f2ada-8a35-4b6b-871e-af3ffbcc44c7', 'image_path' => 'seed/mcdonalds/Choco Mix.jpg'],
                    ['name' => 'Chocolate Croissant', 'description' => 'Chocolate Croissant', 'price' => 10.00, 'category_id' => 16, 'max_per_request' => 5, 'sku' => '970fd76f-e685-4725-b267-7ce7d259c72a', 'image_path' => 'seed/mcdonalds/Chocolate Croissant.jpg'],
                ],
            ],
            [
                'provider_email' => 'carrefour@nubl.com',
                // Grocery: 55 Dairy, 56 Eggs, 69 Breakfast Items, 61 Snacks.
                'items' => [
                    // Dairy (55)
                    ['name' => 'Almarai Full Fat Fresh Milk 2L', 'description' => 'Fresh full-fat cow milk.', 'price' => 11.95, 'category_id' => 55, 'max_per_request' => 8, 'sku' => '106475', 'image_path' => 'seed/carrefour/Almarai Full Fat Fresh Milk 2L.jpg'],
                    ['name' => 'Almarai Fresh Full Fat Laban 2L', 'description' => 'Fresh full-fat laban (drinking yogurt).', 'price' => 10.50, 'category_id' => 55, 'max_per_request' => 8, 'sku' => '106459', 'image_path' => 'seed/carrefour/Almarai Fresh Full Fat Laban 2L.jpg'],
                    ['name' => 'Almarai Full Fat Premium Labneh 700g', 'description' => 'Creamy strained yogurt — labneh.', 'price' => 16.50, 'category_id' => 55, 'max_per_request' => 8, 'sku' => '552068', 'image_path' => 'seed/carrefour/Almarai Full Fat Premium Labneh 700g.jpg'],
                    ['name' => 'Almarai Plain Full Fat Yoghurt 170g Pack of 6', 'description' => 'Six cups plain full-fat yogurt.', 'price' => 24.95, 'category_id' => 55, 'max_per_request' => 8, 'sku' => '357352', 'image_path' => 'seed/carrefour/Almarai Plain Full Fat Yoghurt 170g Pack of 6.jpg'],
                    ['name' => 'Almarai Shredded Mozzarella Cheese 450g', 'description' => 'Shredded mozzarella for baking and pizza.', 'price' => 23.95, 'category_id' => 55, 'max_per_request' => 8, 'sku' => '715022', 'image_path' => 'seed/carrefour/Almarai Shredded Mozzarella Cheese 450g.jpg'],
                    // Eggs (56)
                    ['name' => 'Entaj Large Eggs × 30 pieces', 'description' => 'Large white eggs — tray of 30.', 'price' => 24.95, 'category_id' => 56, 'max_per_request' => 6, 'sku' => '672691', 'image_path' => 'seed/carrefour/Entaj Large Eggs &times; 30 pieces.jpg'],
                    ['name' => 'Alfailaq Large Fresh White Eggs 30 Eggs', 'description' => 'Large fresh white eggs — 30 pack.', 'price' => 23.50, 'category_id' => 56, 'max_per_request' => 6, 'sku' => '593707', 'image_path' => 'seed/carrefour/Alfailaq Large Fresh White Eggs 30 Eggs.jpg'],
                    ['name' => 'Algharbia White Eggs Large × 30', 'description' => 'Large white eggs — tray of 30.', 'price' => 25.25, 'category_id' => 56, 'max_per_request' => 6, 'sku' => '622538', 'image_path' => 'seed/carrefour/Algharbia White Eggs Large &times; 30.jpg'],
                    ['name' => 'Rahima Large White Eggs 30 Pieces', 'description' => 'Large white eggs — 30 pieces.', 'price' => 24.50, 'category_id' => 56, 'max_per_request' => 6, 'sku' => '471728', 'image_path' => 'seed/carrefour/Rahima Large White Eggs 30 Pieces.jpg'],
                    // Breakfast Items (69)
                    ['name' => 'Almarai Breakfast Cream 100g', 'description' => 'Sweet spread for breakfast.', 'price' => 5.25, 'category_id' => 69, 'max_per_request' => 10, 'sku' => '106519', 'image_path' => 'seed/carrefour/Almarai Breakfast Cream 100g.jpg'],
                    ['name' => 'Almarai Breakfast Lite Cream 100g', 'description' => 'Lite breakfast cream spread.', 'price' => 5.25, 'category_id' => 69, 'max_per_request' => 10, 'sku' => '305556', 'image_path' => 'seed/carrefour/Almarai Breakfast Lite Cream 100g.jpg'],
                    ['name' => 'Almarai Greek Yougert Plain 150g x2 +1free', 'description' => 'Greek-style plain yogurt multipack offer.', 'price' => 13.95, 'category_id' => 69, 'max_per_request' => 8, 'sku' => '698753', 'image_path' => 'seed/carrefour/Almarai Greek Yougert Plain 150g x2 +1free.jpg'],
                    ['name' => 'Almarai Full Fat Plain Yoghurt 1kg', 'description' => 'Family-size plain full-fat yogurt.', 'price' => 9.95, 'category_id' => 69, 'max_per_request' => 8, 'sku' => '513171', 'image_path' => 'seed/carrefour/Almarai Full Fat Plain Yoghurt 1kg.jpg'],
                    // Snacks (61)
                    ['name' => 'Almarai Hummus 250g', 'description' => 'Classic chickpea hummus.', 'price' => 9.95, 'category_id' => 61, 'max_per_request' => 8, 'sku' => '646215', 'image_path' => 'seed/carrefour/Almarai Hummus 250g.jpg'],
                    ['name' => 'Almarai Hummus Lemon Twist 250g', 'description' => 'Hummus with lemon.', 'price' => 9.95, 'category_id' => 61, 'max_per_request' => 8, 'sku' => '664657', 'image_path' => 'seed/carrefour/Almarai Hummus Lemon Twist 250g.jpg'],
                    ['name' => 'Mini Babybel Cheddar Cheese, Pack Of 5 Pieces, 100g', 'description' => 'Mini cheddar snack cheese portions.', 'price' => 16.50, 'category_id' => 61, 'max_per_request' => 8, 'sku' => '401455', 'image_path' => 'seed/carrefour/Mini Babybel Cheddar Cheese, Pack Of 5 Pieces, 100g.jpg'],
                ],
            ],
            [
                'provider_email' => 'shawarmahouse@nubl.com',
                // Restaurant: 8 Rice Dishes, 9 Grills, 5 Sandwiches.
                'items' => [
                    // Rice meals → Rice Dishes (8)
                    ['name' => 'Bukhari', 'description' => 'Grilled chicken marinated in authentic Bukhari spices, served with fluffy Bukhari rice.', 'price' => 22.00, 'category_id' => 8, 'max_per_request' => 5, 'sku' => '36929', 'image_path' => 'seed/shawarmahouse/Bukhari.jpg'],
                    ['name' => 'Charcoal-grilled chicken with rice', 'description' => 'Half a chicken grilled over charcoal with Kabsa rice.', 'price' => 24.00, 'category_id' => 8, 'max_per_request' => 5, 'sku' => '37079', 'image_path' => 'seed/shawarmahouse/Charcoal-grilled chicken with rice.jpg'],
                    ['name' => 'Kabsa Chicken', 'description' => 'Rice plate with grilled boneless chicken, tomatoes, onions and hot sauce.', 'price' => 24.00, 'category_id' => 8, 'max_per_request' => 5, 'sku' => '1296768', 'image_path' => 'seed/shawarmahouse/Kabsa Chicken.jpg'],
                    ['name' => 'Shawaya with Rice', 'description' => 'Chicken on the grill with orange rice — Arabic-style plate.', 'price' => 22.00, 'category_id' => 8, 'max_per_request' => 5, 'sku' => '1492056', 'image_path' => 'seed/shawarmahouse/Shawaya with Rice.jpg'],
                    ['name' => 'Mix rice strips', 'description' => 'Strips, rice, lettuce, cucumber, tomato, jalapeño, tahini and spicy salad sauce.', 'price' => 19.00, 'category_id' => 8, 'max_per_request' => 5, 'sku' => '31283', 'image_path' => 'seed/shawarmahouse/Mix rice strips.jpg'],
                    // Grills → Grills (9)
                    ['name' => 'Grilled Chicken Mesahab', 'description' => 'Half grilled chicken with fries, grilled vegetables, garlic and spicy sauce, pickles.', 'price' => 21.00, 'category_id' => 9, 'max_per_request' => 5, 'sku' => '16154', 'image_path' => 'seed/shawarmahouse/Grilled Chicken Mesahab.jpg'],
                    ['name' => 'Charcoal chicken', 'description' => 'Fresh chicken grilled over charcoal with a light smoky flavor.', 'price' => 21.00, 'category_id' => 9, 'max_per_request' => 5, 'sku' => '37078', 'image_path' => 'seed/shawarmahouse/Charcoal chicken.png'],
                    ['name' => 'BBQ Special Box', 'description' => '12 mixed grill skewers, ribs, kebab, 4 mini arayes.', 'price' => 169.00, 'category_id' => 9, 'max_per_request' => 2, 'sku' => '34039', 'image_path' => 'seed/shawarmahouse/BBQ Special Box.jpg'],
                    ['name' => 'Grilled Felet', 'description' => 'Hamour fillet with grilled vegetables, garlic sauce and hot sauce.', 'price' => 37.00, 'category_id' => 9, 'max_per_request' => 4, 'sku' => '1296777', 'image_path' => 'seed/shawarmahouse/Grilled Felet.jpg'],
                    ['name' => 'Chicken Wings', 'description' => 'Chicken wings with grilled peppers, onions, tomatoes, garlic and spicy sauce.', 'price' => 21.00, 'category_id' => 9, 'max_per_request' => 5, 'sku' => '13449', 'image_path' => 'seed/shawarmahouse/Chicken Wings.jpg'],
                    // Shawarma → Sandwiches (5)
                    ['name' => 'Shawarma Nashville', 'description' => 'Arabic-style chicken shawarma with saj bread, fries, strips and sauces.', 'price' => 29.00, 'category_id' => 5, 'max_per_request' => 5, 'sku' => '36457', 'image_path' => 'seed/shawarmahouse/Shawarma Nashville.jpg'],
                    ['name' => 'Mega Shawarma Box', 'description' => 'Mega chicken shawarma sandwich with cheese, fries, buffalo chicken and dips.', 'price' => 29.00, 'category_id' => 5, 'max_per_request' => 5, 'sku' => '36369', 'image_path' => 'seed/shawarmahouse/Mega Shawarma Box.jpg'],
                    ['name' => 'Cheese Shawarma Box', 'description' => 'Chicken or beef shawarma with melted cheese, fries, buffalo chicken and dips.', 'price' => 29.00, 'category_id' => 5, 'max_per_request' => 5, 'sku' => '36366', 'image_path' => 'seed/shawarmahouse/Cheese Shawarma Box.jpg'],
                    ['name' => 'Arabic Shawarma Chiken', 'description' => 'Saj bread with chicken shawarma, garlic sauce, pickles, cabbage and fries.', 'price' => 20.00, 'category_id' => 5, 'max_per_request' => 5, 'sku' => '1284424', 'image_path' => 'seed/shawarmahouse/Arabic Shawarma Chiken.png'],
                    ['name' => 'Big Shawarma', 'description' => 'Chicken or beef shawarma in tortilla with sauces and fresh fries.', 'price' => 15.00, 'category_id' => 5, 'max_per_request' => 5, 'sku' => '33392', 'image_path' => 'seed/shawarmahouse/Big Shawarma.jpg'],
                ],
            ],
        ];

        foreach ($menuItems as $group) {
            $provider = User::where('email', $group['provider_email'])->first();
            if (! $provider) {
                continue;
            }

            foreach ($group['items'] as $item) {
                $categoryId = $item['category_id'];
                $categoryName = MenuItemCategory::query()->whereKey($categoryId)->value('name') ?? '';
                $nameAr = $item['name_ar'] ?? $this->translateToArabic($item['name'] ?? null);
                $descriptionAr = $item['description_ar'] ?? $this->translateToArabic($item['description'] ?? null);

                ProviderMenuItem::updateOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'name' => $item['name'],
                    ],
                    [
                        'name_ar' => $nameAr,
                        'description' => $item['description'],
                        'description_ar' => $descriptionAr,
                        'price' => $item['price'],
                        'category' => $categoryName,
                        'category_id' => $categoryId,
                        'max_per_request' => $item['max_per_request'] ?? null,
                        'is_active' => true,
                        'sku' => $item['sku'] ?? null,
                        'image_path' => $item['image_path'] ?? null,
                    ]
                );
            }
        }
    }

    private function translateToArabic(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $normalized = trim($text);
        $lower = mb_strtolower($normalized);

        $exact = [
            'family meal package' => 'باقة وجبة عائلية',
            'complete meal for 4-6 people' => 'وجبة كاملة تكفي 4-6 أشخاص',
            'daily support order' => 'طلب دعم يومي',
            'single daily meal support' => 'دعم وجبة يومية واحدة',
            'rice & chicken combo' => 'وجبة أرز ودجاج',
            'traditional rice with grilled chicken' => 'أرز تقليدي مع دجاج مشوي',
            'vegetable soup' => 'شوربة خضار',
            'fresh vegetable soup' => 'شوربة خضار طازجة',
            'fresh bread basket' => 'سلة خبز طازج',
            'assorted fresh bread' => 'تشكيلة خبز طازج',
            'weekly assistance' => 'مساعدة أسبوعية',
            'weekly meal package' => 'باقة وجبات أسبوعية',
            'lunch box' => 'وجبة غداء',
            'single lunch box' => 'وجبة غداء فردية',
            'breakfast pack' => 'باقة إفطار',
            'morning breakfast essentials' => 'أساسيات إفطار الصباح',
            'pastry set' => 'تشكيلة معجنات',
            'assorted pastries' => 'معجنات متنوعة',
        ];

        if (isset($exact[$lower])) {
            return $exact[$lower];
        }

        $translated = $normalized;
        $brandMap = [
            'Abukass' => 'أبو كاس',
            'Al Walimah' => 'الوليمة',
            'Sunwhite' => 'صن وايت',
            'Panda' => 'بنده',
            'Al Osra' => 'الأسرة',
            'SIS' => 'إس آي إس',
            'Steviana' => 'ستيفيانا',
            'Afia' => 'عافية',
            'Miza' => 'ميزة',
            'Culina' => 'كولينا',
            'Baya' => 'بايا',
            'Gold Branch' => 'جولد برانش',
            'Danube' => 'الدانوب',
            'Carrefour' => 'كارفور',
            'McChicken' => 'ماك تشيكن',
            'McNuggets' => 'ماك ناجتس',
            'Big Mac' => 'بيج ماك',
            'McRoyale' => 'ماك رويال',
            'McArabia' => 'ماك عربية',
            'Shawarma' => 'شاورما',
        ];
        uksort($brandMap, static fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($brandMap as $en => $ar) {
            $translated = preg_replace('/\b'.preg_quote($en, '/').'\b/ui', $ar, $translated) ?? $translated;
        }

        $dictionary = [
            'Premium' => 'فاخر',
            'Traditional' => 'تقليدي',
            'Fresh' => 'طازج',
            'Classic' => 'كلاسيكي',
            'Organic' => 'عضوي',
            'Low Calorie' => 'قليل السعرات',
            'Family' => 'عائلي',
            'Daily' => 'يومي',
            'Weekly' => 'أسبوعي',
            'Large' => 'كبير',
            'Medium' => 'وسط',
            'Mini' => 'صغير',
            'Meal' => 'وجبة',
            'Meals' => 'وجبات',
            'Package' => 'باقة',
            'Pack' => 'عبوة',
            'Bag' => 'كيس',
            'Set' => 'تشكيلة',
            'Box' => 'صندوق',
            'Combo' => 'كومبو',
            'Deluxe' => 'ديلوكس',
            'Rice' => 'أرز',
            'Basmati' => 'بسمتي',
            'Calrose' => 'كالروز',
            'Chicken' => 'دجاج',
            'Beef' => 'لحم بقري',
            'Soup' => 'شوربة',
            'Bread' => 'خبز',
            'Pastry' => 'معجنات',
            'Pasta' => 'باستا',
            'Sugar' => 'سكر',
            'Sweetener' => 'مُحلّي',
            'Oil' => 'زيت',
            'Olive Oil' => 'زيت زيتون',
            'Corn Oil' => 'زيت ذرة',
            'Sunflower Oil' => 'زيت دوار الشمس',
            'Vegetable Ghee' => 'سمن نباتي',
            'Shampoo' => 'شامبو',
            'Conditioner' => 'بلسم',
            'Deodorant' => 'مزيل عرق',
            'Toothpaste' => 'معجون أسنان',
            'Body Wash' => 'غسول جسم',
            'Soap' => 'صابون',
            'Lotion' => 'لوشن',
            'Face Cream' => 'كريم للوجه',
            'Lip Balm' => 'مرطب شفاه',
            'Hand Wash' => 'غسول يدين',
            'Baby' => 'أطفال',
            'Jelly' => 'جيلي',
            'Milk' => 'حليب',
            'Laban' => 'لبن',
            'Labneh' => 'لبنة',
            'Yoghurt' => 'زبادي',
            'Yogurt' => 'زبادي',
            'Cheese' => 'جبن',
            'Eggs' => 'بيض',
            'Egg' => 'بيضة',
            'Hummus' => 'حمص',
            'Salad' => 'سلطة',
            'Fries' => 'بطاطس مقلية',
            'Nuggets' => 'ناجتس',
            'Wings' => 'أجنحة',
            'Croissant' => 'كرواسون',
            'Cake' => 'كيك',
            'Juice' => 'عصير',
            'Tea' => 'شاي',
            'Orange' => 'برتقال',
            'Black' => 'أسود',
            'White' => 'أبيض',
            'Lite' => 'خفيف',
            'Full Fat' => 'كامل الدسم',
            'Plain' => 'سادة',
            'Shredded' => 'مبشور',
            'Breakfast' => 'إفطار',
            'Grilled' => 'مشوي',
            'Charcoal' => 'فحم',
            'Shawarma' => 'شاورما',
            'French' => 'فرنسي',
            'Spicy' => 'حار',
            'Chocolate' => 'شوكولاتة',
            'Cheddar' => 'شيدر',
            'Anti Dandruff' => 'مضاد للقشرة',
            'Intensive Repair' => 'عناية مكثفة',
            'Cavity Fighter' => 'مكافح للتسوس',
            'Antibacterial' => 'مضاد للبكتيريا',
            'Moisturising' => 'مرطب',
            'Protection' => 'حماية',
            'pieces' => 'حبة',
            'Pieces' => 'حبة',
            'pcs' => 'قطع',
            'Pcs' => 'قطع',
            'for' => 'لـ',
            'with' => 'مع',
            'and' => 'و',
        ];

        uksort($dictionary, static fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($dictionary as $en => $ar) {
            $translated = preg_replace('/\b'.preg_quote($en, '/').'\b/ui', $ar, $translated) ?? $translated;
        }

        $translated = str_replace(['&times;', '&', ' x '], ['×', ' و ', ' × '], $translated);
        $translated = preg_replace('/(?<=\d)\s*[xX]\s*(?=\d)/u', '×', $translated) ?? $translated;
        $translated = preg_replace('/(\d+(?:\.\d+)?)\s*kg\b/ui', '$1 كجم', $translated) ?? $translated;
        $translated = preg_replace('/(\d+(?:\.\d+)?)\s*g\b/ui', '$1 جم', $translated) ?? $translated;
        $translated = preg_replace('/(\d+(?:\.\d+)?)\s*ml\b/ui', '$1 مل', $translated) ?? $translated;
        $translated = preg_replace('/(\d+(?:\.\d+)?)\s*l\b/ui', '$1 لتر', $translated) ?? $translated;
        $translated = preg_replace('/\b(\d+)\s*x\s*(\d+(?:\.\d+)?)\s*(ml|l)\b/ui', '$1×$2 $3', $translated) ?? $translated;
        // Product-name reordering for better Arabic flow.
        $translated = preg_replace('/^(.+?)\s+(بسمتي|كالروز)\s+أرز\s+(\d+(?:\.\d+)?\s*كجم)$/u', 'أرز $2 $1 $3', $translated) ?? $translated;
        $translated = preg_replace('/^(.+?)\s+أرز\s+(\d+(?:\.\d+)?\s*كجم)$/u', 'أرز $1 $2', $translated) ?? $translated;
        $translated = preg_replace('/^(.+?)\s+(زيت)\s+(.+)$/u', '$2 $1 $3', $translated) ?? $translated;
        $translated = preg_replace('/^كبير\s+(.+?)\s+وجبة$/u', 'وجبة $1 كبيرة', $translated) ?? $translated;
        $translated = preg_replace('/^وسط\s+(.+?)\s+وجبة$/u', 'وجبة $1 متوسطة', $translated) ?? $translated;
        $translated = preg_replace('/^صغير\s+(.+?)\s+وجبة$/u', 'وجبة $1 صغيرة', $translated) ?? $translated;
        $translated = preg_replace('/\s+/', ' ', $translated) ?? $translated;
        $translated = preg_replace('/\bأرز\s+أرز\b/u', 'أرز', $translated) ?? $translated;
        $translated = preg_replace('/\b(كجم|جم|مل|لتر)\s+(كجم|جم|مل|لتر)\b/u', '$1', $translated) ?? $translated;

        // Hard guarantee: no English words remain in Arabic fields.
        $translated = preg_replace_callback('/[A-Za-z][A-Za-z0-9\.\-\+\'"]*/u', function (array $match): string {
            return $this->transliterateLatinTokenToArabic($match[0]);
        }, $translated) ?? $translated;

        $translated = preg_replace('/\s+/', ' ', $translated) ?? $translated;

        return trim($translated);
    }

    private function transliterateLatinTokenToArabic(string $token): string
    {
        $map = [
            'a' => 'ا', 'b' => 'ب', 'c' => 'ك', 'd' => 'د', 'e' => 'ي', 'f' => 'ف', 'g' => 'ج', 'h' => 'ه',
            'i' => 'ي', 'j' => 'ج', 'k' => 'ك', 'l' => 'ل', 'm' => 'م', 'n' => 'ن', 'o' => 'و', 'p' => 'ب',
            'q' => 'ق', 'r' => 'ر', 's' => 'س', 't' => 'ت', 'u' => 'و', 'v' => 'ف', 'w' => 'و', 'x' => 'كس',
            'y' => 'ي', 'z' => 'ز',
        ];

        $lower = mb_strtolower($token);
        $result = '';

        $chars = preg_split('//u', $lower, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($chars as $ch) {
            if (isset($map[$ch])) {
                $result .= $map[$ch];
            } elseif (preg_match('/[0-9]/', $ch) === 1) {
                $result .= $ch;
            }
        }

        return $result !== '' ? $result : $token;
    }
}
