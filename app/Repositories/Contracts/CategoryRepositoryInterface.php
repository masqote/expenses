<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Category;
    public function findByName(string $name): ?Category;
    public function create(string $name, ?string $icon = null): Category;
}
