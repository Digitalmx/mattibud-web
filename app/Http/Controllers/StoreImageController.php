<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
    public function deleteImage($storeImageId)
    {
        try {
            $storeImage = StoreImage::findOrFail($storeImageId);
            
            Log::info('Deleting store image', [
                'image_id' => $storeImage->id,
                'store_id' => $storeImage->store_id,
                'image_path' => $storeImage->image_path
            ]);
            
            $storeImage->delete(); // This will trigger the delete event in the model
            
            Log::info('Store image deleted successfully', ['image_id' => $storeImage->id]);
            
            return response()->json(['message' => 'Image deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting store image', [
                'image_id' => $storeImageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to delete image',
                'message' => 'An error occurred while deleting the image. Please try again.'
            ], 500);
        }
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
    
    /**
     * Handle incorrect GET requests to delete endpoint
     *
     * @param StoreImage $storeImage
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleIncorrectDeleteRequest(StoreImage $storeImage)
    {
        return response()->json([
            'error' => 'Method Not Allowed',
            'message' => 'To delete store image with ID ' . $storeImage->id . ', use DELETE method instead of GET.',
            'correct_method' => 'DELETE',
            'correct_endpoint' => '/api/store-images/' . $storeImage->id,
            'image_info' => [
                'id' => $storeImage->id,
                'store_id' => $storeImage->store_id,
                'is_from_pdf' => $storeImage->is_from_pdf
            ]
        ], 405);
    }
}
