<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {

    }

    public function addCategory(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        $category = new Category();
        $category->name = $validatedData['nama'];
        $category->save();

        return response()->json(['message' => 'Category added successfully'], 200);
    }
}
