<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource with optional search and location filtering.
     */
    public function index(Request $request)
    {
        // Validate parameters
        $validator = Validator::make($request->all(), [
            'search_term' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|required_with:longitude,radius_km|numeric',
            'longitude' => 'sometimes|required_with:latitude,radius_km|numeric',
            'radius_km' => 'sometimes|required_with:latitude,longitude|numeric|min:0.1|max:500',
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
        $query = Store::query();
        
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
        if ($request->has(['latitude', 'longitude'])) {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $radiusKm = $request->input('radius_km', 30);
            
            $query->nearby($latitude, $longitude, $radiusKm);
        } else {
            // If no location filter, just order by name
            $query->orderBy('name');
        }
        
        // Get paginated results
        $stores = $query->paginate($limit, ['*'], 'page', $page);
        
        return response()->json($stores);
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
        
        // Add urls to response
        $responseData = $store->toArray();
        $responseData['pdf_url'] = $store->pdf_url;
        $responseData['logo_url'] = $store->logo_url;
        
        return response()->json($responseData, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $store = Store::findOrFail($id);
        $responseData = $store->toArray();
        $responseData['pdf_url'] = $store->pdf_url;
        $responseData['logo_url'] = $store->logo_url;
        return response()->json($responseData);
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
        
        // Add urls to response
        $responseData = $store->toArray();
        $responseData['pdf_url'] = $store->pdf_url;
        $responseData['logo_url'] = $store->logo_url;
        
        return response()->json($responseData);
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
}
