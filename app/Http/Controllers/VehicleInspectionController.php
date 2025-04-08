<?php

namespace App\Http\Controllers;

use App\Models\VehicleInspection;
use Illuminate\Http\Request;

class VehicleInspectionController extends Controller
{
    public function index()
    {
        return VehicleInspection::all();
    }

    public function store(Request $request)
    {
        return VehicleInspection::create($request->all());
    }

    public function show($id)
    {
        return VehicleInspection::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $inspection = VehicleInspection::findOrFail($id);
        $inspection->update($request->all());
        return $inspection;
    }

    public function destroy($id)
    {
        VehicleInspection::destroy($id);
        return response()->noContent();
    }
}
