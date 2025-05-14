@extends('layouts.app')

@section('title', 'Store Details')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #map {
        height: 400px;
        width: 100%;
        margin-bottom: 20px;
    }
    .store-images-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 15px 0;
    }
    .store-image-item {
        width: calc(33.333% - 10px);
        margin-bottom: 10px;
        position: relative;
    }
    .store-image-item img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    .from-pdf-badge {
        position: absolute;
        bottom: 5px;
        left: 5px;
        background-color: rgba(0,0,0,0.6);
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 3px;
    }
    .lightbox {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.9);
    }
    .lightbox-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 90%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .lightbox-close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
    }
    @media (max-width: 768px) {
        .store-image-item {
            width: calc(50% - 10px);
        }
    }
    @media (max-width: 576px) {
        .store-image-item {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Store Details: {{ $store->name }}</h1>
        <div>
            <a href="{{ route('admin.stores.edit', $store) }}" class="btn btn-primary shadow-sm me-2">
                <i class="fas fa-edit fa-sm text-white-50 me-1"></i> Edit Store
            </a>
            <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Stores
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Store Info Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Store Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Store Name:</div>
                        <div class="col-md-8">{{ $store->name }}</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Address:</div>
                        <div class="col-md-8">{{ $store->address ?: 'Not specified' }}</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">City:</div>
                        <div class="col-md-8">{{ $store->city ?: 'Not specified' }}</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Coordinates:</div>
                        <div class="col-md-8">
                            Latitude: {{ $store->latitude }}<br>
                            Longitude: {{ $store->longitude }}
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Logo URL:</div>
                        <div class="col-md-8">
                            @if($store->logo_url)
                                <a href="{{ $store->logo_url }}" target="_blank">{{ $store->logo_url }}</a>
                            @else
                                Not specified
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">PDF URL:</div>
                        <div class="col-md-8">
                            @if($store->pdf_url)
                                <a href="{{ $store->pdf_url }}" target="_blank">{{ $store->pdf_url }}</a>
                            @else
                                Not specified
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Created At:</div>
                        <div class="col-md-8">{{ $store->created_at->format('F d, Y h:i A') }}</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Updated At:</div>
                        <div class="col-md-8">{{ $store->updated_at->format('F d, Y h:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Map & Media Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Location & Media</h6>
                </div>
                <div class="card-body">
                    <!-- Store Location Map -->
                    <h5 class="mb-2">Store Location</h5>
                    <div id="map" class="mb-4"></div>
                    
                    <!-- Store Logo Preview -->
                    @if($store->logo_url)
                        <h5 class="mb-2">Logo Preview</h5>
                        <div class="text-center mb-4">
                            <img src="{{ $store->logo_url }}" alt="{{ $store->name }} Logo" class="img-fluid mb-2" style="max-height: 150px;">
                        </div>
                    @endif
                    
                    <!-- PDF Preview Link -->
                    @if($store->pdf_url)
                        <h5 class="mb-2">PDF Flyer</h5>
                        <div class="text-center mb-4">
                            <a href="{{ $store->pdf_url }}" class="btn btn-primary" target="_blank">
                                <i class="fas fa-file-pdf me-1"></i> View PDF Flyer
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Delete Store Card -->
            <div class="card shadow mb-4 border-left-danger">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Danger Zone</h6>
                </div>
                <div class="card-body">
                    <p>Once you delete a store, there is no going back. Please be certain.</p>
                    <form action="{{ route('admin.stores.destroy', $store) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this store? This action cannot be undone.')">
                            <i class="fas fa-trash me-1"></i> Delete This Store
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Store Images Card -->
    @if($store->images && $store->images->count() > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Store Images</h6>
            <a href="{{ route('admin.stores.edit', $store) }}#images-section" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Add More Images
            </a>
        </div>
        <div class="card-body">
            <div class="store-images-gallery">
                @foreach($store->images as $image)
                <div class="store-image-item">
                    <img src="{{ $image->image_url }}" alt="Store Image" class="store-image" data-image-id="{{ $image->id }}">
                    @if($image->is_from_pdf)
                        <span class="from-pdf-badge">PDF Page {{ $image->pdf_page }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Store Images</h6>
        </div>
        <div class="card-body">
            <div class="text-center py-4">
                <p class="mb-3">No images have been uploaded for this store yet.</p>
                <a href="{{ route('admin.stores.edit', $store) }}#images-section" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Images
                </a>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Image Lightbox -->
    <div id="imageLightbox" class="lightbox">
        <span class="lightbox-close">&times;</span>
        <img class="lightbox-content" id="lightboxImage">
    </div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get existing coordinates
        let lat = {{ $store->latitude }};
        let lng = {{ $store->longitude }};
        
        // Initialize map
        const map = L.map('map').setView([lat, lng], 13);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add marker at store's location
        L.marker([lat, lng]).addTo(map)
            .bindPopup("<b>{{ $store->name }}</b><br>{{ $store->address }}").openPopup();
            
        // Image lightbox functionality
        const lightbox = document.getElementById('imageLightbox');
        const lightboxImg = document.getElementById('lightboxImage');
        const lightboxClose = document.querySelector('.lightbox-close');
        const storeImages = document.querySelectorAll('.store-image');
        
        storeImages.forEach(image => {
            image.addEventListener('click', function() {
                lightbox.style.display = 'block';
                lightboxImg.src = this.src;
            });
        });
        
        lightboxClose.addEventListener('click', function() {
            lightbox.style.display = 'none';
        });
        
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                lightbox.style.display = 'none';
            }
        });
    });
</script>
@endsection