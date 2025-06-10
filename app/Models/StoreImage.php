<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class StoreImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'image_path',
        'is_from_pdf',
        'pdf_page',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_from_pdf' => 'boolean',
        'pdf_page' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the store that owns the image.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the image URL
     * 
     * @return string
     */
    public function getImageUrlAttribute()
    {
        return URL::asset('storage/' . $this->image_path);
    }

    /**
     * Delete the image file when the model is deleted
     */
    protected static function booted()
    {
        static::deleting(function ($storeImage) {
            if (!is_null($storeImage->image_path)) {
                try {
                    // Check if file exists before deleting
                    if (Storage::disk('public')->exists($storeImage->image_path)) {
                        Storage::disk('public')->delete($storeImage->image_path);
                    } else {
                        Log::warning("File not found: {$storeImage->image_path}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to delete image file: {$storeImage->image_path}. Error: " . $e->getMessage());
                }
            }
        });
    }
}
