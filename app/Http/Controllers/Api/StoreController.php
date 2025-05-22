<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource with optional search and location filtering.
     */
    public function index(Request $request)
    {
        try {
            // Validate parameters
            $validator = Validator::make($request->all(), [
                'search_term' => 'sometimes|string|max:255',
                'latitude' => 'sometimes|numeric|between:-90,90',
                'longitude' => 'sometimes|numeric|between:-180,180',
                'radius_km' => 'sometimes|numeric|min:0|max:1000',
                'page' => 'sometimes|integer|min:1',
                'limit' => 'sometimes|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Default pagination values
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);
            
            // Start the query
            $query = Store::with('images');
            
            // Apply search term filter if provided
            if ($request->has('search_term')) {
                $searchTerm = $request->input('search_term');
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('city', 'like', "%{$searchTerm}%")
                      ->orWhere('address', 'like', "%{$searchTerm}%");
                });
            }
            
            // Apply location filter if provided
            if ($request->has('latitude') && $request->has('longitude')) {
                $latitude = (float) $request->input('latitude');
                $longitude = (float) $request->input('longitude');
                $radiusKm = (float) $request->input('radius_km', 30);
                
                $query->nearby($latitude, $longitude, $radiusKm);
            } else {
                // If no location filter, just order by name
                $query->orderBy('name');
            }
        
            // Get paginated results with added URLs
            $stores = $query->paginate($limit, ['*'], 'page', $page);
            
            // Create a custom response with added URLs
            $response = [
                'current_page' => $stores->currentPage(),
                'data' => [],
                'first_page_url' => $stores->url(1),
                'from' => $stores->firstItem(),
                'last_page' => $stores->lastPage(),
                'last_page_url' => $stores->url($stores->lastPage()),
                'next_page_url' => $stores->nextPageUrl(),
                'path' => $stores->path(),
                'per_page' => $stores->perPage(),
                'prev_page_url' => $stores->previousPageUrl(),
                'to' => $stores->lastItem(),
                'total' => $stores->total(),
            ];
            
            // Process each store to add URLs and sanitize data
            foreach ($stores as $store) {
                // Convert to array and sanitize any potential invalid UTF-8 characters
                $storeData = $this->sanitizeData($store->toArray());
                $storeData['logo_url'] = $store->logo_path ? URL::asset('storage/' . $store->logo_path) : null;
                $storeData['pdf_url'] = $store->pdf_path ? URL::asset('storage/' . $store->pdf_path) : null;
                
                // Get store images and their URLs
                $storeData['images'] = $store->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_url' => $image->image_url,
                        'is_from_pdf' => $image->is_from_pdf,
                        'pdf_page' => $image->pdf_page,
                        'sort_order' => $image->sort_order
                    ];
                });
                
                $response['data'][] = $storeData;
            }

            return response()->json($response, 200, [
                'Content-Type' => 'application/json;charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Store index error: ' . $e->getMessage());
            
            // Return a friendly error message
            return response()->json(['error' => 'An error occurred while retrieving stores.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'logo_file' => 'sometimes|nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // Changed from logo_url to logo_file
            'pdf_file' => 'sometimes|nullable|file|mimes:pdf|max:10240',
            'address' => 'sometimes|nullable|string',
            'city' => 'sometimes|nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except(['pdf_file', 'logo_file']);
        
        // Handle logo file upload
        if ($request->hasFile('logo_file') && $request->file('logo_file')->isValid()) {
            $logo = $request->file('logo_file');
            $logoFilename = 'store_logo_' . Str::slug($request->name) . '_' . time() . '.' . $logo->getClientOriginalExtension();
            $logoPath = $logo->storeAs('images/stores', $logoFilename, 'public');
            $data['logo_path'] = $logoPath;
        }
        
        // Handle PDF file upload
        if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
            $pdf = $request->file('pdf_file');
            $filename = 'store_' . Str::slug($request->name) . '_' . time() . '.' . $pdf->getClientOriginalExtension();
            $path = $pdf->storeAs('pdfs/stores', $filename, 'public');
            $data['pdf_path'] = $path;
        }

        // Create the store
        $store = Store::create($data);
        
        // Add urls to response and sanitize data
        $responseData = $this->sanitizeData($store->toArray());
        $responseData['pdf_url'] = $store->pdf_url;
        $responseData['logo_url'] = $store->logo_url;
        
        return response()->json($responseData, 201, [
            'Content-Type' => 'application/json;charset=UTF-8',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $store = Store::with('images')->findOrFail($id);
        $responseData = $this->sanitizeData($store->toArray());
        $responseData['pdf_url'] = $store->pdf_url;
        $responseData['logo_url'] = $store->logo_url;
        
        // Format images to include the image URL
        $responseData['images'] = $store->images->map(function ($image) {
            return [
                'id' => $image->id,
                'image_url' => $image->image_url,
                'is_from_pdf' => $image->is_from_pdf,
                'pdf_page' => $image->pdf_page,
                'sort_order' => $image->sort_order
            ];
        });
        
        return response()->json($responseData, 200, [
            'Content-Type' => 'application/json;charset=UTF-8',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Find the store
        $store = Store::findOrFail($id);
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'logo_file' => 'sometimes|nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // Changed from logo_url to logo_file
            'pdf_file' => 'sometimes|nullable|file|mimes:pdf|max:10240',
            'address' => 'sometimes|nullable|string',
            'city' => 'sometimes|nullable|string|max:100',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except(['pdf_file', 'logo_file']);
        
        // Handle logo file upload
        if ($request->hasFile('logo_file') && $request->file('logo_file')->isValid()) {
            $logo = $request->file('logo_file');
            $logoFilename = 'store_logo_' . Str::slug($request->input('name', $store->name)) . '_' . time() . '.' . $logo->getClientOriginalExtension();
            $logoPath = $logo->storeAs('images/stores', $logoFilename, 'public');
            $data['logo_path'] = $logoPath;
        }
        
        // Handle PDF file upload
        if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
            $pdf = $request->file('pdf_file');
            $filename = 'store_' . Str::slug($request->input('name', $store->name)) . '_' . time() . '.' . $pdf->getClientOriginalExtension();
            $path = $pdf->storeAs('pdfs/stores', $filename, 'public');
            $data['pdf_path'] = $path;
        }

        // Update the store
        $store->update($data);
        
        // Add urls to response and sanitize data
        $responseData = $this->sanitizeData($store->toArray());
        $responseData['pdf_url'] = $store->pdf_url;
        $responseData['logo_url'] = $store->logo_url;
        
        return response()->json($responseData, 200, [
            'Content-Type' => 'application/json;charset=UTF-8',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $store = Store::findOrFail($id);
        $store->delete();
        
        return response()->json(null, 204);
    }

    /**
     * Remove or fix malformed UTF-8 characters from data array
     *
     * @param array $data The data to sanitize
     * @return array The sanitized data
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recursively sanitize nested arrays
                $sanitized[$key] = $this->sanitizeData($value);
            } elseif (is_string($value)) {
                // Remove invalid UTF-8 sequences
                $sanitized[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                
                // If that didn't work, replace with empty string
                if ($sanitized[$key] === false) {
                    $sanitized[$key] = '';
                }
            } else {
                // Non-string values don't need sanitizing
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}
