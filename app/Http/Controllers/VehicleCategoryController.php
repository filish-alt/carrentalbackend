<?php

namespace App\Http\Controllers;

use App\Models\VehicleCategory;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Vehicle Categories",
 *     description="Vehicle category management endpoints"
 * )
 */
class VehicleCategoryController extends Controller
{
    public function index()
    {
        return VehicleCategory::all();
    }

    public function store(Request $request)
    {
        return VehicleCategory::create($request->all());
    }

    public function show($id)
    {
        return VehicleCategory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $category = VehicleCategory::findOrFail($id);
        $category->update($request->all());
        return $category;
    }

    public function destroy($id)
    {
        VehicleCategory::destroy($id);
        return response()->noContent();
    }
}
