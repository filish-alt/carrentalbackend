<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceRecord;
class MaintenanceController extends Controller
{
    //
    public function index()
    {
        return response()->json(MaintenanceRecord::with('car')->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'maintenance_date' => 'required|date',
            'note' => 'nullable|string',
            'status' => 'required|in:pending,open,completed',
            'cost' => 'nullable|numeric|min:0',
        ]);

        $record = MaintenanceRecord::create($validated);

        return response()->json(['message' => 'Maintenance record created.', 'data' => $record], 201);
    }

    public function show($id)
    {
        $record = MaintenanceRecord::with('car')->findOrFail($id);
        return response()->json($record);
    }
    
    public function update(Request $request, $id)
    {
        $record = MaintenanceRecord::findOrFail($id);

        $validated = $request->validate([
            'car_id' => 'sometimes|exists:cars,id',
            'maintenance_date' => 'sometimes|date',
            'note' => 'nullable|string',
            'status' => 'sometimes|in:pending,open,completed',
            'cost' => 'nullable|numeric|min:0',
        ]);

        $record->update($validated);

        return response()->json(['message' => 'Maintenance record updated.', 'data' => $record]);
    }

}
