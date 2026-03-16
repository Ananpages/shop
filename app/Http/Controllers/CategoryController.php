<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    // GET /api/categories
    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->withCount(['products' => fn($q) => $q->where('status', 'active')])
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }
}
