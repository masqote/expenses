<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function all(): Collection
    {
        return Category::orderBy('is_default', 'desc')->orderBy('name')->get();
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function findByName(string $name): ?Category
    {
        return Category::where('name', $name)->first();
    }

    public function create(string $name, ?string $icon = null): Category
    {
        return Category::create(['name' => $name, 'icon' => $icon, 'is_default' => false]);
    }
}
