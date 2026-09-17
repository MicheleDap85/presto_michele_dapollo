<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public $categories = [
        'Elettronica',
        'Abbigliamento',
        'Salute e bellezza',
        'Casa e giardinaggio',
        'Giocattoli',
        'Sport',
        'Animali domestici',
        'Libri e riviste',
        'Accessori',
        'Motori',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->categories as $category) {
            Category::create([
                'name' => $category,
            ]);
        }
    }
}
