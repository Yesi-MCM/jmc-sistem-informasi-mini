<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegionController extends Controller
{
    /**
     * Search districts (kecamatan) with autocomplete.
     * Requires minimum 3 characters.
     */
    public function searchDistricts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:3'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $search = $request->query('q');

        $districts = District::with('regency.province')
            ->where('name', 'LIKE', '%' . $search . '%')
            ->limit(20)
            ->get();

        $results = $districts->map(function ($district) {
            $regency = $district->regency;
            $province = $regency ? $regency->province : null;

            return [
                'district_id' => $district->id,
                'district_name' => $district->name,
                'regency_id' => $regency ? $regency->id : null,
                'regency_name' => $regency ? $regency->name : null,
                'province_id' => $province ? $province->id : null,
                'province_name' => $province ? $province->name : null,
                'full_label' => sprintf(
                    '%s, %s, %s',
                    $district->name,
                    $regency ? $regency->name : '',
                    $province ? $province->name : ''
                )
            ];
        });

        return response()->json([
            'data' => $results
        ]);
    }
}
