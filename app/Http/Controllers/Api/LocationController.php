<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    /**
     * Google Places API base URL
     */
    protected $placesApiBaseUrl = 'https://maps.googleapis.com/maps/api';
    
    /**
     * Get Google Places API key from config
     */
    protected function getApiKey()
    {
        return config('services.google.places_api_key');
    }
    
    /**
     * Proxy autocomplete search requests to Google Places API
     */
    public function autocomplete(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'input' => 'required|string|min:2|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            // Call Google Places Autocomplete API
            $response = Http::get($this->placesApiBaseUrl . '/place/autocomplete/json', [
                'input' => $request->input('input'),
                'types' => 'geocode',
                'key' => $this->getApiKey(),
            ]);
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch location suggestions'], 500);
        }
    }
    
    /**
     * Proxy place details requests to Google Places API
     */
    public function details(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'placeId' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            // Call Google Places Details API
            $response = Http::get($this->placesApiBaseUrl . '/place/details/json', [
                'place_id' => $request->input('placeId'),
                'fields' => 'geometry,formatted_address',
                'key' => $this->getApiKey(),
            ]);
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch location details'], 500);
        }
    }
}
