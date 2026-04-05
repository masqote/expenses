<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function __construct(private CategoryRepositoryInterface $categoryRepo) {}

    public function index()
    {
        return response()->json($this->categoryRepo->all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);

        $category = $this->categoryRepo->create($data['name'], $data['icon'] ?? null);

        return response()->json($category, 201);
    }
}
