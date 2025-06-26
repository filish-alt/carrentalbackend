<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = Audit::with('user')->orderBy('created_at', 'desc');

        //Filter by user 
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        //Filter by event type (created, updated, deleted)
        if ($request->has('event')) {
            $query->where('event', $request->input('event'));
        }

        // Filter by model type (Car, Home)
        if ($request->has('model')) {
            $query->where('auditable_type', 'like', '%' . $request->input('model') . '%');
        }

        //Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->input('start_date'),
                $request->input('end_date')
            ]);
        }

        // Search by any keyword inside old/new values or URL
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('old_values', 'like', '%' . $search . '%')
                  ->orWhere('new_values', 'like', '%' . $search . '%')
                  ->orWhere('url', 'like', '%' . $search . '%');
            });
        }

        //  Paginate 
        $audits = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $audits
        ]);
    }
}
