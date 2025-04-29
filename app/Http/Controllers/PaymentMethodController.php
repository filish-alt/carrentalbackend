<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    // List Payment Methods for the Authenticated User
    public function index()
    {
        $user = Auth::user();
        $paymentMethods = $user->paymentMethods()->get();
        return response()->json($paymentMethods);
    }

    // Add Payment Method
    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string|in:card,cash,bank_transfer',
            'details' => 'nullable|array',
        ]);

        $paymentMethod = Auth::user()->paymentMethods()->create([
            'payment_method' => $request->payment_method,
            'details' => $request->details,
        ]);

        return response()->json($paymentMethod, 201);
    }

    // Update Payment Method
    public function update(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'sometimes|required|string|in:card,cash,bank_transfer',
            'details' => 'sometimes|nullable|array',
        ]);

        $paymentMethod = Auth::user()->paymentMethods()->findOrFail($id);
        $paymentMethod->update($request->only(['payment_method', 'details']));

        return response()->json($paymentMethod);
    }

    // Delete Payment Method
    public function destroy($id)
    {
        $paymentMethod = Auth::user()->paymentMethods()->findOrFail($id);
        $paymentMethod->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    // Retrieve Single Payment Method
    public function show($id)
    {
        $paymentMethod = Auth::user()->paymentMethods()->findOrFail($id);
        return response()->json($paymentMethod);
    }

    // Get Payment Methods by User ID
    public function getByUserId($userId)
    {
        $paymentMethods = PaymentMethod::where('user_id', $userId)->get();

        return response()->json($paymentMethods);
    }

}
