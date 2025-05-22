<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Display a listing of the stores.
     */
    public function index()
    {
        $stores = Store::orderBy('name')->paginate(10);
        return view('admin.stores.index', compact('stores'));
    }

    /**
     * Show the form for creating a new store.
     */
    public function create()
    {
        return view('admin.stores.create');
    }

    /**
     * Store a newly created store in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'logo_file' => 'nullable|file|max:2048',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
            'store_images.*' => 'nullable|image|max:5120',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'upload_type' => 'nullable|string|in:images,pdf',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except(['pdf_file', 'logo_file', 'store_images', 'upload_type']);
        
        // Handle logo file upload
        if ($request->hasFile('logo_file') && $request->file('logo_file')->isValid()) {
            $logo = $request->file('logo_file');
            $logoFilename = 'store_logo_' . Str::slug($request->name) . '_' . time() . '.' . $logo->getClientOriginalExtension();
            $logoPath = $logo->storeAs('images/stores', $logoFilename, 'public');
            $data['logo_path'] = $logoPath;
        }
        
        // Create the store first
        $store = Store::create($data);
        
        // Handle uploads based on upload type
        $uploadType = $request->input('upload_type', 'images');
        
        if ($uploadType === 'pdf') {
            // Handle PDF file upload
            if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                $pdf = $request->file('pdf_file');
                $filename = 'store_' . Str::slug($request->name) . '_' . time() . '.' . $pdf->getClientOriginalExtension();
                $path = $pdf->storeAs('pdfs/stores', $filename, 'public');
                $store->update(['pdf_path' => $path]);
                
                // Process PDF to images
                $store->processPdfToImages($path);
            }
        } else {
            // Handle multiple store images
            if ($request->hasFile('store_images')) {
                $sortOrder = 1;
                foreach ($request->file('store_images') as $image) {
                    if ($image->isValid()) {
                        $imageName = 'stores/' . $store->id . '/' . time() . '-' . $sortOrder . '-' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
                        $image->storeAs('public', $imageName);
                        
                        // Create store image record
                        $store->images()->create([
                            'image_path' => $imageName,
                            'is_from_pdf' => false,
                            'sort_order' => $sortOrder
                        ]);
                        
                        $sortOrder++;
                    }
                }
            }
        }

        return redirect()->route('admin.stores.index')
            ->with('success', 'Store created successfully.');
    }

    /**
     * Display the specified store.
     */
    public function show(Store $store)
    {
        return view('admin.stores.show', compact('store'));
    }

    /**
     * Show the form for editing the specified store.
     */
    public function edit(Store $store)
    {
        return view('admin.stores.edit', compact('store'));
    }

    /**
     * Update the specified store in storage.
     */
    public function update(Request $request, Store $store)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'logo_file' => 'nullable|file||max:2048',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
            'store_images.*' => 'nullable|image|max:5120',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'upload_type' => 'nullable|string|in:images,pdf',
        ]);

        if ($validator->fails()) {
            Log::debug('Validation failed', [
                'errors' => $validator->errors()->all()
            ]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except(['pdf_file', 'logo_file', 'store_images', 'upload_type']);
        // Handle logo file upload
        if ($request->hasFile('logo_file') && $request->file('logo_file')->isValid()) {
            $logo = $request->file('logo_file');
            $logoFilename = 'store_logo_' . Str::slug($request->name) . '_' . time() . '.' . $logo->getClientOriginalExtension();
            $logoPath = $logo->storeAs('images/stores', $logoFilename, 'public');
            $data['logo_path'] = $logoPath;
        }
        
        // Update the store data first
        $store->update($data);
        // Handle uploads based on upload type
        $uploadType = $request->input('upload_type', 'images');
        Log::debug('Upload type', ['upload_type' => $uploadType]);
        if ($uploadType === 'pdf') {
            // Handle PDF file upload
            if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                $pdf = $request->file('pdf_file');
                $filename = 'store_' . Str::slug($request->name) . '_' . time() . '.' . $pdf->getClientOriginalExtension();
                $path = $pdf->storeAs('pdfs/stores', $filename, 'public');
                $store->update(['pdf_path' => $path]);
                
                // Process PDF to images
                $store->processPdfToImages($path);
            } else {
                Log::debug('No valid PDF file uploaded');
            }
        } else {
            // Handle multiple store images
            if ($request->hasFile('store_images')) {
                $sortOrder = $store->images()->max('sort_order') + 1;
                foreach ($request->file('store_images') as $image) {
                    if ($image->isValid()) {
                        $imageName = 'stores/' . $store->id . '/' . time() . '-' . $sortOrder . '-' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
                        $image->storeAs('public', $imageName);
                        
                        // Create store image record
                        $store->images()->create([
                            'image_path' => $imageName,
                            'is_from_pdf' => false,
                            'sort_order' => $sortOrder
                        ]);
                        
                        $sortOrder++;
                    }
                }
            } else {
                Log::debug('No store images uploaded');
            }
        }
        return redirect()->route('admin.stores.show', $store)
            ->with('success', 'Store updated successfully.');
    }

    /**
     * Remove the specified store from storage.
     */
    public function destroy(Store $store)
    {
        $store->delete();

        return redirect()->route('admin.stores.index')
            ->with('success', 'Store deleted successfully.');
    }
}
