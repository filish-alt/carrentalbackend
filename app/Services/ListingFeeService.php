<?php

namespace App\Services;

use App\Models\ListingFee;

class ListingFeeService
{
    public function getAllFees($filters = [])
    {
        $query = ListingFee::query();

        if (isset($filters['item_type'])) {
            $query->where('item_type', $filters['item_type']);
        }

        if (isset($filters['listing_type'])) {
            $query->where('listing_type', $filters['listing_type']);
        }

        return $query->paginate(10);
    }

    public function createFee(array $data)
    {
        $fee = ListingFee::create($data);

        return [
            'message' => 'Fee created',
            'fee' => $fee
        ];
    }

    public function getFee($id)
    {
        return ListingFee::findOrFail($id);
    }

    public function updateFee(array $data, ListingFee $listingFee)
    {
        $listingFee->update($data);

        return [
            'message' => 'Fee updated',
            'fee' => $listingFee
        ];
    }

    public function deleteFee(ListingFee $listingFee)
    {
        $listingFee->delete();

        return ['message' => 'Fee deleted'];
    }

    public function getActiveFee($itemType, $listingType)
    {
        return ListingFee::where('item_type', $itemType)
            ->where('listing_type', $listingType)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
