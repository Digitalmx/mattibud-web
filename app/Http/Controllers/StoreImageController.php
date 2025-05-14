<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreImageController extends Controller
{
    /**
     * Upload a new image for a store
     *
     * @param Request $request
     * @param Store $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request, Store $store)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            'sort_order' => 'nullable|integer'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = 'stores/' . $store->id . '/' . time() . '-' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
            
            // Store the image
            $image->storeAs('public', $imageName);
            
            // Create store image record
            $storeImage = $store->images()->create([
                'image_path' => $imageName,
                'is_from_pdf' => false,
                'sort_order' => $request->sort_order ?? (StoreImage::where('store_id', $store->id)->max('sort_order') + 1)
            ]);
            
            return response()->json([
                'message' => 'Image uploaded successfully',
                'image' => $storeImage,
                'image_url' => $storeImage->image_url
            ]);
        }
        
        return response()->json(['message' => 'No image was uploaded'], 400);
    }
    
    /**
     * Upload a PDF and convert each page to an image
     *
     * @param Request $request
     * @param Store $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadPdf(Request $request, Store $store)
    {
        $request->validate([
            'pdf' => 'required|mimes:pdf|max:20480', // 20MB max
        ]);
        
        // Handle PDF upload
        if ($request->hasFile('pdf')) {
            $pdf = $request->file('pdf');
            $pdfName = 'stores/' . $store->id . '/' . time() . '-' . Str::slug(pathinfo($pdf->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf';
            
            // Store the PDF
            $pdf->storeAs('public', $pdfName);
            
            // Update the store model with the PDF path
            $store->update(['pdf_path' => $pdfName]);
            
            // Process PDF to images (this is done asynchronously to avoid timeout)
            $store->processPdfToImages($pdfName);
            
            return response()->json([
                'message' => 'PDF uploaded and converted to images successfully',
                'pdf_url' => $store->pdf_url,
                'images' => $store->images()->where('is_from_pdf', true)->get()
            ]);
        }
        
        return response()->json(['message' => 'No PDF was uploaded'], 400);
    }
    
    /**
     * Delete a store image
     *
     * @param StoreImage $storeImage
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(StoreImage $storeImage)
    {
        $storeImage->delete(); // This will trigger the delete event in the model
        
        return response()->json(['message' => 'Image deleted successfully']);
    }
    
    /**
     * Update the sort order of images
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSortOrder(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:store_images,id',
            'images.*.sort_order' => 'required|integer'
        ]);
        
        foreach ($request->images as $imageData) {
            StoreImage::where('id', $imageData['id'])->update(['sort_order' => $imageData['sort_order']]);
        }
        
        return response()->json(['message' => 'Image order updated successfully']);
    }
    
    /**
     * Get all images for a store
     *
     * @param Store $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function getImages(Store $store)
    {
        $images = $store->images;
        
        return response()->json([
            'images' => $images,
            'image_urls' => $images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->image_url,
                    'is_from_pdf' => $image->is_from_pdf,
                    'pdf_page' => $image->pdf_page,
                    'sort_order' => $image->sort_order
                ];
            })
        ]);
    }
}
