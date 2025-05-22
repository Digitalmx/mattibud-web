<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

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
        try {
            if (isset($this->longitude) && isset($this->latitude)) {
                $longitude = (float) $this->longitude;
                $latitude = (float) $this->latitude;
                $this->attributes['location'] = DB::raw("ST_SRID(POINT({$longitude}, {$latitude}), 4326)");
            }
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to set location point: ' . $e->getMessage());
        }
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
                Log::warning('Imagick extension is not loaded - using fallback method for PDF conversion');
                $this->processPdfWithFallback($pdfPath);
                return;
            }
            
            // Check if the Imagick class exists
            if (!class_exists('Imagick')) {
                Log::warning('Imagick class does not exist - using fallback method for PDF conversion');
                $this->processPdfWithFallback($pdfPath);
                return;
            }
            
            Log::info('Starting PDF to image conversion with Imagick: ' . $pdfPath);
            
            // Get the absolute path to the PDF file
            $absolutePath = Storage::disk('public')->path($pdfPath);
            
            // Verify the PDF file exists
            if (!file_exists($absolutePath)) {
                Log::error('PDF file does not exist at path: ' . $absolutePath);
                throw new \Exception('PDF file not found');
            }
            
            // Try direct Imagick approach first (more reliable than the third-party packages)
            try {
                $this->convertPdfWithNativeImagick($absolutePath, $pdfPath);
                return;
            } catch (\Exception $e) {
                Log::warning('Native Imagick conversion failed, trying alternate method: ' . $e->getMessage());
                // Continue with alternative approach
            }
            
            // Try using system tools as a fallback with exec
            try {
                if ($this->canUseExec()) {
                    $this->convertPdfWithSystemTools($absolutePath, $pdfPath);
                    return;
                }
            } catch (\Exception $e) {
                Log::warning('System tools conversion failed: ' . $e->getMessage());
                // Continue with final fallback
            }
            
            // If all else fails, use a simple approach to extract PDF content
            $this->convertPdfWithPurePhp($absolutePath, $pdfPath);
            
        } catch (\Exception $e) {
            // Log the error
            Log::error('PDF conversion error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Try fallback method with placeholders
            $this->processPdfWithFallback($pdfPath);
        }
    }
    
    /**
     * Check if we can use PHP's exec function
     * 
     * @return bool
     */
    private function canUseExec() 
    {
        // Check if exec is available and not disabled
        if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
            return true;
        }
        return false;
    }
    
    /**
     * Convert PDF to images using system tools like Ghostscript or Poppler Utils
     * 
     * @param string $absolutePath Full path to the PDF file
     * @param string $pdfPath Storage path to the PDF file
     * @return void
     */
    private function convertPdfWithSystemTools($absolutePath, $pdfPath) 
    {
        // First, try to count pages using pdfinfo if available
        $pageCount = 0;
        
        // Try with pdfinfo (from poppler-utils)
        if ($this->commandExists('pdfinfo')) {
            $command = "pdfinfo \"$absolutePath\"";
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0) {
                foreach ($output as $line) {
                    if (preg_match('/Pages:\s+(\d+)/', $line, $matches)) {
                        $pageCount = (int)$matches[1];
                        break;
                    }
                }
            }
        }
        
        // If pdfinfo failed, try to count using pdftk
        if ($pageCount === 0 && $this->commandExists('pdftk')) {
            $command = "pdftk \"$absolutePath\" dump_data | grep NumberOfPages";
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0 && isset($output[0])) {
                if (preg_match('/NumberOfPages:\s+(\d+)/', $output[0], $matches)) {
                    $pageCount = (int)$matches[1];
                }
            }
        }
        
        // If we still don't have a page count, use our regex method
        if ($pageCount === 0) {
            $pdfContent = file_get_contents($absolutePath);
            $pageCount = $this->countPdfPages($pdfContent);
        }
        
        // If we still can't determine page count, assume at least 1 page
        if ($pageCount === 0) {
            $pageCount = 1;
        }
        
        Log::info("PDF has $pageCount pages, converting using system tools");
        
        // Try converting with different tools, starting with pdftoppm (from poppler-utils)
        $success = false;
        
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $imageName = 'stores/' . $this->id . '/pdf-page-' . $pageNumber . '-' . time() . '.jpg';
            $imagePath = Storage::disk('public')->path($imageName);

            // Make sure the directory exists
            if (!file_exists(dirname($imagePath))) {
                mkdir(dirname($imagePath), 0755, true);
            }

            $success = false;

            // Try pdftoppm first (from poppler-utils)
            if ($this->commandExists('pdftoppm') && !$success) {
                // Generate JPG from PDF page
                $command = "pdftoppm -jpeg -f $pageNumber -l $pageNumber -r 150 \"$absolutePath\" \"" . 
                            pathinfo($imagePath, PATHINFO_DIRNAME) . '/' . pathinfo($imagePath, PATHINFO_FILENAME) . "\"";
                
                exec($command, $output, $returnVar);
                
                // Check if the command succeeded and created the file
                if ($returnVar === 0) {
                    // pdftoppm creates files with -1.jpg suffix, rename to our desired name
                    $pdftoppmPath = pathinfo($imagePath, PATHINFO_DIRNAME) . '/' . 
                                    pathinfo($imagePath, PATHINFO_FILENAME) . '-1.jpg';
                    
                    if (file_exists($pdftoppmPath)) {
                        rename($pdftoppmPath, $imagePath);
                        $success = true;
                    }
                }
            }

            // Try Ghostscript if pdftoppm failed
            if ($this->commandExists('gs') && !$success) {
                $command = "gs -dNOPAUSE -dBATCH -sDEVICE=jpeg -r150 -dFirstPage=$pageNumber -dLastPage=$pageNumber " .
                           "-sOutputFile=\"$imagePath\" \"$absolutePath\" 2>&1";
                
                exec($command, $output, $returnVar);
                
                if ($returnVar === 0 && file_exists($imagePath)) {
                    $success = true;
                }
            }

            // Only create StoreImage record if conversion succeeded
            if ($success) {
                $this->images()->create([
                    'image_path' => $imageName,
                    'is_from_pdf' => true,
                    'pdf_page' => $pageNumber,
                    'sort_order' => $pageNumber,
                ]);
                Log::info('Successfully processed page ' . $pageNumber . ' using system tools');
            } else {
                Log::warning('Failed to convert page ' . $pageNumber . ' using system tools, skipping image creation.');
            }
        }
    }
    
    /**
     * Check if a command exists and is executable on the system
     * 
     * @param string $command The command to check
     * @return bool Whether the command exists
     */
    private function commandExists($command) 
    {
        $return = shell_exec("which $command 2>/dev/null");
        return !empty($return);
    }
    
    /**
     * Pure PHP approach to extract text from PDF and generate image with content
     * This is a very limited approach but works as a last resort
     * 
     * @param string $absolutePath Full path to the PDF file
     * @param string $pdfPath Storage path to the PDF file
     * @return void
     */
    private function convertPdfWithPurePhp($absolutePath, $pdfPath) 
    {
        Log::info('Using pure PHP approach to extract PDF content');
        
        // First, let's try to count pages
        $pdfContent = file_get_contents($absolutePath);
        $numberOfPages = $this->countPdfPages($pdfContent);
        
        if ($numberOfPages === 0) {
            // If we can't determine page count, assume at least 1 page
            $numberOfPages = 1;
        }
        
        // Extract some text from PDF to make more informative placeholders
        $extractedText = $this->extractTextFromPdf($pdfContent);
        
        // Create image for each page
        for ($pageNumber = 1; $pageNumber <= $numberOfPages; $pageNumber++) {
            // Create image path
            $imageName = 'stores/' . $this->id . '/pdf-page-' . $pageNumber . '-' . time() . '.jpg';
            $imagePath = Storage::disk('public')->path($imageName);
            
            // Make sure the directory exists
            if (!file_exists(dirname($imagePath))) {
                mkdir(dirname($imagePath), 0755, true);
            }
            
            // Create a more informative placeholder image using GD Library
            $width = 800;
            $height = 1120; // A4 proportions roughly
            
            // Create blank image and add text
            $image = imagecreatetruecolor($width, $height);
            $bgColor = imagecolorallocate($image, 245, 245, 245);
            $textColor = imagecolorallocate($image, 50, 50, 50);
            $accentColor = imagecolorallocate($image, 61, 101, 181);
            
            // Fill the background
            imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
            
            // Add border
            imagerectangle($image, 0, 0, $width-1, $height-1, $accentColor);
            
            // Add store name
            $text = "PDF Content - " . $this->name;
            $fontSize = 5;
            $textWidth = imagefontwidth($fontSize) * strlen($text);
            $textX = ($width - $textWidth) / 2;
            imagestring($image, $fontSize, $textX, 50, $text, $textColor);
            
            // Add page number
            $pageText = "Page " . $pageNumber . " of " . $numberOfPages;
            $pageTextWidth = imagefontwidth($fontSize) * strlen($pageText);
            $pageTextX = ($width - $pageTextWidth) / 2;
            imagestring($image, $fontSize, $pageTextX, 100, $pageText, $textColor);
            
            // Add PDF text extract preview (if available)
            if (!empty($extractedText)) {
                $lines = explode("\n", wordwrap($extractedText, 80, "\n"));
                $startY = 180;
                $lineHeight = 20;
                $contentFont = 2;
                
                imagestring($image, 3, 30, 150, "PDF Content Preview:", $accentColor);
                
                // Only show first 15 lines to avoid overcrowding
                $maxLines = min(15, count($lines));
                for ($i = 0; $i < $maxLines; $i++) {
                    imagestring($image, $contentFont, 30, $startY + ($i * $lineHeight), 
                                substr($lines[$i], 0, 90), $textColor);
                }
                
                if (count($lines) > 15) {
                    imagestring($image, $contentFont, 30, $startY + (15 * $lineHeight), 
                                "... (content truncated)", $textColor);
                }
            } else {
                imagestring($image, 3, 30, 200, "Could not extract text content from PDF", $textColor);
            }
            
            // Add note about conversion method
            $infoText = "Limited PDF preview (using PHP without Imagick)";
            $infoTextWidth = imagefontwidth(3) * strlen($infoText);
            $infoTextX = ($width - $infoTextWidth) / 2;
            imagestring($image, 3, $infoTextX, $height - 50, $infoText, $accentColor);
            
            // Output image to file
            imagejpeg($image, $imagePath, 90);
            imagedestroy($image);
            
            // Create StoreImage record for this PDF page
            $this->images()->create([
                'image_path' => $imageName,
                'is_from_pdf' => true,
                'pdf_page' => $pageNumber,
                'sort_order' => $pageNumber,
            ]);
            
            Log::info('Created PDF content preview image for page ' . $pageNumber);
        }
    }
    
    /**
     * Extract some text content from a PDF
     * 
     * @param string $pdfContent Binary PDF content
     * @return string Extracted text
     */
    private function extractTextFromPdf($pdfContent) 
    {
        // Simple regex to extract some text from PDF
        // This is not a full PDF parser, just extracts some visible text
        $text = '';
        
        // Extract text objects
        preg_match_all('/\[(.*?)\]TJ/', $pdfContent, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                // Clean up the text by removing PDF escape sequences
                $cleanText = preg_replace('/[\\\\\(\\\\)]/', '', $match);
                $cleanText = preg_replace('/[^a-zA-Z0-9\s\.,;:\'"-]/', '', $cleanText);
                
                if (strlen(trim($cleanText)) > 0) {
                    $text .= ' ' . $cleanText;
                }
            }
        }
        
        // Alternative approach
        preg_match_all('/\(([^\)]*)\)Tj/', $pdfContent, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $cleanText = preg_replace('/[\\\\\(\\\\)]/', '', $match);
                $cleanText = preg_replace('/[^a-zA-Z0-9\s\.,;:\'"-]/', '', $cleanText);
                
                if (strlen(trim($cleanText)) > 0) {
                    $text .= ' ' . $cleanText;
                }
            }
        }
        
        return trim($text);
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
        // Read the PDF file (all pages)
        $imagick->readImage($absolutePath . '[0-999]'); // Read all pages
        // Get the number of pages
        $totalPages = $imagick->getNumberImages();
        Log::info('Native Imagick found ' . $totalPages . ' pages in PDF');
        // Process each page
        for ($pageNumber = 0; $pageNumber < $totalPages; $pageNumber++) {
            $imagick->setIteratorIndex($pageNumber);
            $page = $imagick->getImage(); // clone the current page
            $page->setImageFormat('jpg');
            $page->setImageCompressionQuality(90);
            $page->setImageBackgroundColor('white');
            $img = $page->flattenImages();
            $pageNumberDisplay = $pageNumber + 1;
            $imageName = 'stores/' . $this->id . '/pdf-page-' . $pageNumberDisplay . '-' . time() . '.jpg';
            $imagePath = Storage::disk('public')->path($imageName);
            if (!file_exists(dirname($imagePath))) {
                mkdir(dirname($imagePath), 0755, true);
            }
            $img->writeImage($imagePath);
            $this->images()->create([
                'image_path' => $imageName,
                'is_from_pdf' => true,
                'pdf_page' => $pageNumberDisplay,
                'sort_order' => $pageNumberDisplay,
            ]);
            $img->destroy();
            $page->destroy();
            Log::info('Successfully saved image for page ' . $pageNumberDisplay . ' using native Imagick');
        }
        $imagick->clear();
        $imagick->destroy();
        Log::info('Native Imagick PDF conversion completed successfully');
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
            Log::error('PDF fallback processing error: ' . $e->getMessage());
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
        // Cast values to ensure proper type
        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        $radius_km = (float) $radius_km;
        
        // Convert km to m for calculation
        $radius_m = $radius_km * 1000;
        
        try {
            // MySQL 8+ spatial calculation
            return $query->selectRaw('*, ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) as distance', [$longitude, $latitude])
                ->whereRaw('ST_Distance_Sphere(location, ST_SRID(POINT(?, ?), 4326)) <= ?', [$longitude, $latitude, $radius_m])
                ->orderBy('distance');
        } catch (\Exception $e) {
            // Fallback if spatial functions are not available
            // Log the error
            Log::error('Spatial query failed: ' . $e->getMessage());
            
            // Just return stores ordered by name as fallback
            return $query->orderBy('name');
        }
    }
}
