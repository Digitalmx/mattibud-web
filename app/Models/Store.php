<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\PdfToImage\Pdf;

class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'logo_path', // Changed from logo_url to logo_path
        'pdf_path',
        'address',
        'city',
        'latitude',
        'longitude',
    ];

    /**
     * Set the location point when coordinates are updated.
     */
    protected static function booted()
    {
        static::creating(function ($store) {
            if (isset($store->latitude) && isset($store->longitude)) {
                $store->setLocationPoint();
            }
        });

        static::updating(function ($store) {
            if ($store->isDirty(['latitude', 'longitude'])) {
                $store->setLocationPoint();
            }
            
            // Delete old PDF file if it's being updated
            if ($store->isDirty('pdf_path') && !is_null($store->getOriginal('pdf_path'))) {
                // Delete associated PDF images
                $store->deleteAssociatedPdfImages();
                // Delete PDF file
                Storage::disk('public')->delete($store->getOriginal('pdf_path'));
            }
            
            // Delete old logo file if it's being updated
            if ($store->isDirty('logo_path') && !is_null($store->getOriginal('logo_path'))) {
                Storage::disk('public')->delete($store->getOriginal('logo_path'));
            }
        });

        static::deleting(function ($store) {
            // Delete the PDF file when the store is deleted
            if (!is_null($store->pdf_path)) {
                Storage::disk('public')->delete($store->pdf_path);
            }
            
            // Delete the logo file when the store is deleted
            if (!is_null($store->logo_path)) {
                Storage::disk('public')->delete($store->logo_path);
            }
            
            // Images will be deleted via the cascade relationship in migration
        });
    }

    /**
     * Delete all images associated with this store's PDF
     */
    public function deleteAssociatedPdfImages()
    {
        $pdfImages = $this->images()->where('is_from_pdf', true)->get();
        
        foreach ($pdfImages as $image) {
            $image->delete(); // This will trigger the delete event on StoreImage
        }
    }

    /**
     * Set the location point based on latitude and longitude.
     */
    public function setLocationPoint()
    {
        $this->attributes['location'] = DB::raw("ST_SRID(POINT({$this->longitude}, {$this->latitude}), 4326)");
    }

    /**
     * Get PDF URL attribute
     * 
     * @return string|null
     */
    public function getPdfUrlAttribute()
    {
        return $this->pdf_path ? URL::asset('storage/' . $this->pdf_path) : null;
    }
    
    /**
     * Get Logo URL attribute
     * 
     * @return string|null
     */
    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? URL::asset('storage/' . $this->logo_path) : null;
    }

    /**
     * Get the images for the store.
     */
    public function images()
    {
        return $this->hasMany(StoreImage::class)->orderBy('sort_order');
    }
    
    /**
     * Process PDF file and convert each page to an image
     *
     * @param string $pdfPath Path to the PDF file in storage
     * @return void
     */
    public function processPdfToImages($pdfPath)
    {
        try {
            // Force PHP to refresh its extension list
            clearstatcache(true);
            
            // Check if Imagick is properly loaded
            if (!extension_loaded('imagick')) {
                \Log::warning('Imagick extension is not loaded - using fallback method for PDF conversion');
                $this->processPdfWithFallback($pdfPath);
                return;
            }
            
            // Check if the Imagick class exists
            if (!class_exists('Imagick')) {
                \Log::warning('Imagick class does not exist - using fallback method for PDF conversion');
                $this->processPdfWithFallback($pdfPath);
                return;
            }
            
            \Log::info('Starting PDF to image conversion with Imagick: ' . $pdfPath);
            
            // Get the absolute path to the PDF file
            $absolutePath = Storage::disk('public')->path($pdfPath);
            
            // Verify the PDF file exists
            if (!file_exists($absolutePath)) {
                \Log::error('PDF file does not exist at path: ' . $absolutePath);
                throw new \Exception('PDF file not found');
            }
            
            // Try direct Imagick approach first (more reliable than the Spatie package in some cases)
            try {
                $this->convertPdfWithNativeImagick($absolutePath, $pdfPath);
                return;
            } catch (\Exception $e) {
                \Log::warning('Native Imagick conversion failed, trying Spatie package: ' . $e->getMessage());
                // Continue with Spatie package as fallback
            }
            
            // Use the Spatie package as a fallback
            $pdf = new Pdf($absolutePath);
            $totalPages = $pdf->getNumberOfPages();
            
            \Log::info('PDF has ' . $totalPages . ' pages');
            
            for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {
                $imageName = 'stores/' . $this->id . '/pdf-page-' . $pageNumber . '-' . time() . '.jpg';
                $imagePath = Storage::disk('public')->path($imageName);
                
                // Make sure the directory exists
                if (!file_exists(dirname($imagePath))) {
                    mkdir(dirname($imagePath), 0755, true);
                }
                
                // Convert PDF page to image
                \Log::info('Converting page ' . $pageNumber . ' to image');
                $pdf->setPage($pageNumber)
                    ->setOutputFormat('jpg')
                    ->setResolution(150) // Increase resolution for better quality
                    ->saveImage($imagePath);
                
                // Verify the image was created
                if (!file_exists($imagePath)) {
                    \Log::warning('Failed to create image at path: ' . $imagePath);
                    throw new \Exception('Failed to create image');
                }
                
                // Create StoreImage record for this PDF page
                $this->images()->create([
                    'image_path' => $imageName,
                    'is_from_pdf' => true,
                    'pdf_page' => $pageNumber,
                    'sort_order' => $pageNumber,
                ]);
                
                \Log::info('Successfully saved image for page ' . $pageNumber);
            }
            
            \Log::info('PDF conversion completed successfully');
            
        } catch (\Exception $e) {
            // Log the error
            \Log::error('PDF conversion error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Try fallback method
            $this->processPdfWithFallback($pdfPath);
        }
    }
    
    /**
     * Convert PDF to images using native Imagick directly
     * This method bypasses the Spatie package for more direct control
     *
     * @param string $absolutePath Full path to the PDF file
     * @param string $pdfPath Storage path to the PDF file
     * @return void
     */
    private function convertPdfWithNativeImagick($absolutePath, $pdfPath)
    {
        // Create a new Imagick instance
        $imagick = new \Imagick();
        
        // Set resolution before reading the file (important for PDF quality)
        $imagick->setResolution(150, 150);
        
        // Read the PDF file
        $imagick->readImage($absolutePath);
        
        // Get the number of pages
        $totalPages = $imagick->getNumberImages();
        
        \Log::info('Native Imagick found ' . $totalPages . ' pages in PDF');
        
        // Process each page
        for ($pageNumber = 0; $pageNumber < $totalPages; $pageNumber++) {
            // Select the page (0-indexed in Imagick)
            $imagick->setIteratorIndex($pageNumber);
            
            // Convert to image format
            $imagick->setImageFormat('jpg');
            
            // Set image compression quality
            $imagick->setImageCompressionQuality(90);
            
            // Set white background for transparent areas
            $imagick->setImageBackgroundColor('white');
            $imagick = $imagick->flattenImages();
            
            // Create filename and path
            $pageNumberDisplay = $pageNumber + 1; // For display, use 1-indexed
            $imageName = 'stores/' . $this->id . '/pdf-page-' . $pageNumberDisplay . '-' . time() . '.jpg';
            $imagePath = Storage::disk('public')->path($imageName);
            
            // Make sure the directory exists
            if (!file_exists(dirname($imagePath))) {
                mkdir(dirname($imagePath), 0755, true);
            }
            
            // Write the image
            $imagick->writeImage($imagePath);
            
            // Create StoreImage record for this PDF page
            $this->images()->create([
                'image_path' => $imageName,
                'is_from_pdf' => true,
                'pdf_page' => $pageNumberDisplay,
                'sort_order' => $pageNumberDisplay,
            ]);
            
            \Log::info('Successfully saved image for page ' . $pageNumberDisplay . ' using native Imagick');
        }
        
        // Free up resources
        $imagick->clear();
        $imagick->destroy();
        
        \Log::info('Native Imagick PDF conversion completed successfully');
    }

    /**
     * Fallback method to process PDF when Imagick is not available
     * This method creates a placeholder image for each PDF page
     *
     * @param string $pdfPath Path to the PDF file in storage
     * @return void
     */
    public function processPdfWithFallback($pdfPath)
    {
        try {
            // Generate PDF preview image using a third-party service or create a placeholder
            // First, let's try to get the total number of pages with a simpler PDF library
            $pdfContent = Storage::disk('public')->get($pdfPath);
            $numberOfPages = $this->countPdfPages($pdfContent);
            
            if ($numberOfPages === 0) {
                // If we can't determine page count, assume at least 1 page
                $numberOfPages = 1;
            }
            
            // Create placeholder image for each page
            for ($pageNumber = 1; $pageNumber <= $numberOfPages; $pageNumber++) {
                // Create a placeholder image
                $placeholderPath = $this->createPdfPlaceholder($pageNumber);
                
                // Create StoreImage record for this PDF page
                $this->images()->create([
                    'image_path' => $placeholderPath,
                    'is_from_pdf' => true,
                    'pdf_page' => $pageNumber,
                    'sort_order' => $pageNumber,
                ]);
            }
            
            // Save metadata about the PDF for display in UI
            $this->update([
                'pdf_processing_status' => 'placeholder', // Add this field to the stores table
            ]);
        } catch (\Exception $e) {
            \Log::error('PDF fallback processing error: ' . $e->getMessage());
        }
    }

    /**
     * Count the number of pages in a PDF
     *
     * @param string $pdfContent The binary content of the PDF
     * @return int Number of pages
     */
    private function countPdfPages($pdfContent)
    {
        try {
            // Simple regex approach to count pages
            preg_match_all("/\/Page\W/", $pdfContent, $matches);
            $pageCount = count($matches[0]);
            
            if ($pageCount > 0) {
                return $pageCount;
            }
            
            // Alternative approach
            $count = 0;
            $regex = "/\/Type\s*\/Page\b/";
            $pages = preg_match_all($regex, $pdfContent, $matches);
            
            return $pages;
        } catch (\Exception $e) {
            return 1; // Return 1 as fallback
        }
    }

    /**
     * Create a placeholder image for a PDF page
     *
     * @param int $pageNumber The page number for the placeholder
     * @return string The path to the created placeholder image
     */
    private function createPdfPlaceholder($pageNumber)
    {
        // Define the path for our placeholder
        $imageName = 'stores/' . $this->id . '/pdf-placeholder-' . $pageNumber . '-' . time() . '.jpg';
        $imagePath = Storage::disk('public')->path($imageName);
        
        // Make sure the directory exists
        if (!file_exists(dirname($imagePath))) {
            mkdir(dirname($imagePath), 0755, true);
        }
        
        // Create a placeholder image using GD Library (which is usually available)
        $width = 800;
        $height = 1120; // A4 proportions roughly
        
        // Create blank image and add text
        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        $textColor = imagecolorallocate($image, 50, 50, 50);
        $accentColor = imagecolorallocate($image, 61, 101, 181);
        
        // Fill the background
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // Add border
        imagerectangle($image, 0, 0, $width-1, $height-1, $accentColor);
        
        // Add store name
        $text = "PDF Preview - " . $this->name;
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textX = ($width - $textWidth) / 2;
        imagestring($image, $fontSize, $textX, 50, $text, $textColor);
        
        // Add page number
        $pageText = "Page " . $pageNumber;
        $pageTextWidth = imagefontwidth($fontSize) * strlen($pageText);
        $pageTextX = ($width - $pageTextWidth) / 2;
        imagestring($image, $fontSize, $pageTextX, 100, $pageText, $textColor);
        
        // Add info text
        $infoText = "Install PHP Imagick extension to see actual PDF content";
        $infoTextWidth = imagefontwidth(4) * strlen($infoText);
        $infoTextX = ($width - $infoTextWidth) / 2;
        imagestring($image, 4, $infoTextX, $height/2, $infoText, $accentColor);
        
        // Output image to file
        imagejpeg($image, $imagePath, 90);
        imagedestroy($image);
        
        return $imageName;
    }

    /**
     * Scope a query to find stores within a specified radius of a location.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $latitude
     * @param float $longitude
     * @param float $radius_km
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNearby($query, $latitude, $longitude, $radius_km = 30)
    {
        // Convert km to m for calculation
        $radius_m = $radius_km * 1000;
        
        // MySQL 8+ spatial calculation
        return $query->selectRaw('*, ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) as distance', [$longitude, $latitude])
            ->whereRaw('ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) <= ?', [$longitude, $latitude, $radius_m])
            ->orderBy('distance');
    }
}
