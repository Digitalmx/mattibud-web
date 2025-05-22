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
     * Return predefined places for address search
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function predefinedPlaces(Request $request)
    {
        $places = [
            [
                'id' => 'oslo_east',
                'description' => 'Oslo Øst',
                'place_id' => 'oslo_east',
                'structured_formatting' => [
                    'main_text' => 'Oslo Øst',
                    'secondary_text' => 'Oslo, Norway'
                ],
                'latitude' => 59.9139,
                'longitude' => 10.8025
            ],
            [
                'id' => 'oslo_west',
                'description' => 'Oslo Vest',
                'place_id' => 'oslo_west',
                'structured_formatting' => [
                    'main_text' => 'Oslo Vest',
                    'secondary_text' => 'Oslo, Norway'
                ],
                'latitude' => 59.9267,
                'longitude' => 10.7045
            ],
            [
                'id' => 'oslo_south',
                'description' => 'Oslo Sør',
                'place_id' => 'oslo_south',
                'structured_formatting' => [
                    'main_text' => 'Oslo Sør',
                    'secondary_text' => 'Oslo, Norway'
                ],
                'latitude' => 59.8617,
                'longitude' => 10.7797
            ],
            [
                'id' => 'oslo_center',
                'description' => 'Oslo Sentrum',
                'place_id' => 'oslo_center',
                'structured_formatting' => [
                    'main_text' => 'Oslo Sentrum',
                    'secondary_text' => 'Oslo, Norway'
                ],
                'latitude' => 59.9139,
                'longitude' => 10.7522
            ],
            [
                'id' => 'asker_baerum',
                'description' => 'Asker og Bærum',
                'place_id' => 'asker_baerum',
                'structured_formatting' => [
                    'main_text' => 'Asker og Bærum',
                    'secondary_text' => 'Viken, Norway'
                ],
                'latitude' => 59.8354,
                'longitude' => 10.4694
            ],
            [
                'id' => 'nedre_romerike',
                'description' => 'Nedre Romerike',
                'place_id' => 'nedre_romerike',
                'structured_formatting' => [
                    'main_text' => 'Nedre Romerike',
                    'secondary_text' => 'Viken, Norway'
                ],
                'latitude' => 59.9483,
                'longitude' => 11.0432
            ],
            [
                'id' => 'ovre_romerike',
                'description' => 'Øvre Romerike',
                'place_id' => 'ovre_romerike',
                'structured_formatting' => [
                    'main_text' => 'Øvre Romerike',
                    'secondary_text' => 'Viken, Norway'
                ],
                'latitude' => 60.1691,
                'longitude' => 11.1480
            ],
            [
                'id' => 'follo',
                'description' => 'Follo',
                'place_id' => 'follo',
                'structured_formatting' => [
                    'main_text' => 'Follo',
                    'secondary_text' => 'Viken, Norway'
                ],
                'latitude' => 59.7221,
                'longitude' => 10.8348
            ]
        ];
        
        // If search term is provided, filter the places
        if ($request->has('input') && !empty($request->input('input'))) {
            $searchTerm = strtolower($request->input('input'));
            $places = array_filter($places, function($place) use ($searchTerm) {
                return str_contains(strtolower($place['description']), $searchTerm);
            });
        }
        
        return response()->json([
            'status' => 'OK',
            'predictions' => array_values($places)
        ]);
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
     * Get place details for predefined places
     */
    public function predefinedPlaceDetails(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'placeId' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $placeId = $request->input('placeId');
        
        $places = [
            'oslo_east' => [
                'name' => 'Oslo Øst',
                'latitude' => 59.9139,
                'longitude' => 10.8025,
                'formatted_address' => 'Oslo Øst, Oslo, Norway'
            ],
            'oslo_west' => [
                'name' => 'Oslo Vest',
                'latitude' => 59.9267,
                'longitude' => 10.7045,
                'formatted_address' => 'Oslo Vest, Oslo, Norway'
            ],
            'oslo_south' => [
                'name' => 'Oslo Sør',
                'latitude' => 59.8617,
                'longitude' => 10.7797,
                'formatted_address' => 'Oslo Sør, Oslo, Norway'
            ],
            'oslo_center' => [
                'name' => 'Oslo Sentrum',
                'latitude' => 59.9139,
                'longitude' => 10.7522,
                'formatted_address' => 'Oslo Sentrum, Oslo, Norway'
            ],
            'asker_baerum' => [
                'name' => 'Asker og Bærum',
                'latitude' => 59.8354,
                'longitude' => 10.4694,
                'formatted_address' => 'Asker og Bærum, Viken, Norway'
            ],
            'nedre_romerike' => [
                'name' => 'Nedre Romerike',
                'latitude' => 59.9483,
                'longitude' => 11.0432,
                'formatted_address' => 'Nedre Romerike, Viken, Norway'
            ],
            'ovre_romerike' => [
                'name' => 'Øvre Romerike',
                'latitude' => 60.1691,
                'longitude' => 11.1480,
                'formatted_address' => 'Øvre Romerike, Viken, Norway'
            ],
            'follo' => [
                'name' => 'Follo',
                'latitude' => 59.7221,
                'longitude' => 10.8348,
                'formatted_address' => 'Follo, Viken, Norway'
            ]
        ];
        
        if (isset($places[$placeId])) {
            return response()->json([
                'status' => 'OK',
                'result' => [
                    'formatted_address' => $places[$placeId]['formatted_address'],
                    'geometry' => [
                        'location' => [
                            'lat' => $places[$placeId]['latitude'],
                            'lng' => $places[$placeId]['longitude']
                        ]
                    ],
                    'name' => $places[$placeId]['name']
                ]
            ]);
        }
        
        return response()->json([
            'status' => 'NOT_FOUND',
            'error_message' => 'Place not found'
        ], 404);
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
