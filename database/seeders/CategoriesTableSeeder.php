<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesTableSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'General Discussion', 'description' => 'Talk about anything related to the community.'],
            ['name' => 'Coding Challenges', 'description' => 'Test your skills with fun coding problems.'],
            ['name' => 'Tutorials', 'description' => 'Learn and share knowledge with tutorials.'],
            ['name' => 'Bug Reports', 'description' => 'Report bugs and issues.'],
            ['name' => 'Feature Requests', 'description' => 'Suggest new features for the platform.'],
            ['name' => 'Off-Topic', 'description' => 'Chat about anything not covered in other categories.'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
