@extends('layouts.app')

@section('title', 'Edit Store')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #map {
        height: 400px;
        width: 100%;
        margin-bottom: 20px;
    }
    .preview-image {
        max-width: 150px;
        max-height: 150px;
        margin-top: 10px;
    }
    .search-results {
        position: absolute;
        z-index: 1000;
        background: white;
        width: 100%;
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 0.25rem;
        display: none;
    }
    .search-results ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .search-results li {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
    .search-results li:hover {
        background-color: #f8f9fa;
    }
    .address-container {
        position: relative;
    }
    .store-image-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }
    .store-image-item {
        position: relative;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px;
        width: 150px;
    }
    .store-image-item img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 4px;
    }
    .store-image-item .delete-btn {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        text-align: center;
        line-height: 22px;
        font-size: 10px;
        cursor: pointer;
    }
    .store-image-item .from-pdf {
        position: absolute;
        bottom: 5px;
        left: 5px;
        background: rgba(0,0,0,0.6);
        color: white;
        font-size: 10px;
        padding: 2px 5px;
        border-radius: 3px;
    }
    .image-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    .image-preview-item {
        position: relative;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px;
        width: 150px;
    }
    .image-preview-item img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 4px;
    }
    .image-preview-item .remove-btn {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        text-align: center;
        line-height: 22px;
        font-size: 10px;
        cursor: pointer;
    }
    .upload-type-selector {
        margin-bottom: 15px;
    }
</style>
@endsection

@section('content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Store: {{ $store->name }}</h1>
        <div>
            <a href="{{ route('admin.stores.show', $store) }}" class="btn btn-info shadow-sm me-2">
                <i class="fas fa-eye fa-sm text-white-50 me-1"></i> View Store
            </a>
            <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Stores
            </a>
        </div>
    </div>

    <!-- Store Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Store Information</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.stores.update', $store) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Store Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $store->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $store->city) }}">
                        @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3 address-container">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $store->address) }}" placeholder="Type to search address...">
                    <div class="search-results" id="search-results">
                        <ul id="results-list"></ul>
                    </div>
                    <small class="form-text text-muted">Search for an address to automatically fill coordinates</small>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="latitude" class="form-label">Latitude <span class="text-danger">*</span></label>
                        <input type="number" step="any" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude', $store->latitude) }}" required>
                        @error('latitude')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="longitude" class="form-label">Longitude <span class="text-danger">*</span></label>
                        <input type="number" step="any" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude', $store->longitude) }}" required>
                        @error('longitude')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Map for selecting location -->
                <div class="mb-3">
                    <label class="form-label">Select Location on Map</label>
                    <div id="map"></div>
                    <small class="text-muted">Click on the map to set latitude and longitude</small>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="logo_file" class="form-label">Logo Image</label>
                        <input type="file" class="form-control @error('logo_file') is-invalid @enderror" id="logo_file" name="logo_file" accept="image/jpeg,image/png,image/gif,image/jpg">
                        @error('logo_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Upload a logo image (JPG, PNG, GIF - max size: 2MB)</small>
                        
                        @if($store->logo_path)
                            <div class="mt-2">
                                <p class="mb-1">Current Logo:</p>
                                <img src="{{ $store->logo_url }}" alt="Store Logo" class="preview-image border">
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Existing Store Images Section -->
                @if($store->images && $store->images->count() > 0)
                <div class="mb-4">
                    <h5 class="mb-3">Existing Store Images</h5>
                    <p>These images are currently associated with this store. You can remove any of them.</p>
                    
                    <div class="store-image-gallery">
                        @foreach($store->images as $image)
                            <div class="store-image-item" data-image-id="{{ $image->id }}">
                                <img src="{{ $image->image_url }}" alt="Store Image">
                                <button type="button" class="delete-btn delete-store-image" data-image-id="{{ $image->id }}">
                                    <i class="fas fa-times"></i>
                                </button>
                                @if($image->is_from_pdf)
                                    <span class="from-pdf">PDF Page {{ $image->pdf_page }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
                
                <!-- Add More Images Section -->
                <div class="mb-4">
                    <h5 class="mb-3">Add More Media</h5>
                    
                    <div class="upload-type-selector">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="upload_type" id="upload_images" value="images" checked>
                            <label class="form-check-label" for="upload_images">Upload Images</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="upload_type" id="upload_pdf" value="pdf">
                            <label class="form-check-label" for="upload_pdf">Upload PDF (converts to images)</label>
                        </div>
                    </div>
                    
                    <div id="image_upload_section">
                        <div class="mb-3">
                            <label class="form-label">Additional Store Images</label>
                            <input type="file" class="form-control @error('store_images') is-invalid @enderror" id="store_images" name="store_images[]" accept="image/jpeg,image/png,image/gif,image/jpg" multiple>
                            @error('store_images')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Upload multiple store images (JPG, PNG, GIF - max size: 5MB each)</small>
                            
                            <div id="image_preview" class="image-preview mt-2"></div>
                            
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add_more_images">
                                <i class="fas fa-plus"></i> Add More Images
                            </button>
                        </div>
                    </div>
                    
                    <div id="pdf_upload_section" style="display: none;">
                        <div class="mb-3">
                            <label for="pdf_file" class="form-label">PDF File</label>
                            <input type="file" class="form-control @error('pdf_file') is-invalid @enderror" id="pdf_file" name="pdf_file" accept="application/pdf">
                            @error('pdf_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Upload a PDF file. Each page will be converted to an image. (max size: 10MB)</small>
                            
                            @if($store->pdf_path)
                                <div class="mt-2">
                                    <div class="alert alert-info">
                                        <strong>Note:</strong> Uploading a new PDF will replace the current one and all its associated images.
                                    </div>
                                    <a href="{{ $store->pdf_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-pdf me-1"></i> View Current PDF
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Store
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get existing coordinates
        let lat = {{ old('latitude', $store->latitude) }};
        let lng = {{ old('longitude', $store->longitude) }};
        
        // Initialize map
        const map = L.map('map').setView([lat, lng], 13);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add marker at store's location
        let marker = L.marker([lat, lng], {
            draggable: true
        }).addTo(map);
        
        // Update lat/lng inputs when marker is moved
        function updateLatLng(latlng) {
            document.getElementById('latitude').value = latlng.lat.toFixed(8);
            document.getElementById('longitude').value = latlng.lng.toFixed(8);
        }
        
        // Event handlers
        marker.on('dragend', function(e) {
            updateLatLng(marker.getLatLng());
        });
        
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateLatLng(e.latlng);
        });
        
        // Initialize with current values
        updateLatLng(marker.getLatLng());
        
        // File upload type toggle
        const uploadTypeRadios = document.querySelectorAll('input[name="upload_type"]');
        const imageUploadSection = document.getElementById('image_upload_section');
        const pdfUploadSection = document.getElementById('pdf_upload_section');
        
        uploadTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'images') {
                    imageUploadSection.style.display = 'block';
                    pdfUploadSection.style.display = 'none';
                } else {
                    imageUploadSection.style.display = 'none';
                    pdfUploadSection.style.display = 'block';
                }
            });
        });
        
        // Image preview for multiple images
        const storeImagesInput = document.getElementById('store_images');
        const imagePreviewContainer = document.getElementById('image_preview');
        
        storeImagesInput.addEventListener('change', function(e) {
            previewImages(this.files);
        });
        
        // Add more images button
        document.getElementById('add_more_images').addEventListener('click', function() {
            storeImagesInput.click();
        });
        
        function previewImages(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (!file.type.startsWith('image/')) continue;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'image-preview-item';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Image preview';
                    
                    const removeBtn = document.createElement('div');
                    removeBtn.className = 'remove-btn';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.addEventListener('click', function() {
                        previewItem.remove();
                    });
                    
                    previewItem.appendChild(img);
                    previewItem.appendChild(removeBtn);
                    imagePreviewContainer.appendChild(previewItem);
                }
                
                reader.readAsDataURL(file);
            }
        }
        
        // Delete existing store images
        const deleteButtons = document.querySelectorAll('.delete-store-image');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const imageId = this.getAttribute('data-image-id');
                if (confirm('Are you sure you want to delete this image?')) {
                    deleteStoreImage(imageId, this);
                }
            });
        });
        
        function deleteStoreImage(imageId, buttonElement) {
            // Get the CSRF token from the meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`/api/store-images/${imageId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin' // Important for cookies/session handling
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Network response was not ok: ' + response.statusText);
            })
            .then(data => {
                // Remove the image item from DOM
                const imageItem = buttonElement.closest('.store-image-item');
                imageItem.remove();
                
                // Show success message
                alert('Image deleted successfully');
            })
            .catch(error => {
                console.error('Error deleting image:', error);
                alert('Error deleting image. Please try again.');
            });
        }

        // Address search functionality
        const addressInput = document.getElementById('address');
        const searchResults = document.getElementById('search-results');
        const resultsList = document.getElementById('results-list');
        const cityInput = document.getElementById('city');

        // Add debounce function to avoid too many API calls
        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    func.apply(context, args);
                }, wait);
            };
        }
        
        // Address input keyup for suggestions with debounce
        addressInput.addEventListener('keyup', debounce(function() {
            if (addressInput.value.length >= 2) {
                searchAddress();
            } else {
                searchResults.style.display = 'none';
            }
        }, 300));

        // Address input focus out
        addressInput.addEventListener('blur', function() {
            // Delay hiding results to allow for clicking on results
            setTimeout(() => {
                searchResults.style.display = 'none';
            }, 200);
        });

        // Address input focus in
        addressInput.addEventListener('focus', function() {
            if (addressInput.value.length >= 2) {
                searchAddress();
            }
        });

        // Oslo areas quick suggestions
        const osloAreas = [
            { name: 'Oslo Øst', address: 'Strømsveien 196, Alnabru, Oslo', lat: 59.9500, lng: 10.7300 },
            { name: 'Oslo Vest', address: 'Hoffsveien 10, Skøyen, Oslo', lat: 59.9160, lng: 10.7100 },
            { name: 'Oslo Sør', address: 'Mortensrudveien 3, Mortensrud, Oslo', lat: 59.8511, lng: 10.8176 },
            { name: 'Oslo Sentrum', address: 'Karl Johans gate 3, Oslo', lat: 59.9115, lng: 10.7579 },
            { name: 'Asker og Bærum', address: 'Sandviksveien 184, Sandvika', lat: 59.8313, lng: 10.4176 },
            { name: 'Nedre Romerike', address: 'Strømsvegen 55, Lillestrøm', lat: 59.9551, lng: 11.0379 },
            { name: 'Øvre Romerike', address: 'Jessheim Storsenter, Jessheim', lat: 59.9800, lng: 10.9500 },
            { name: 'Follo', address: 'Ski Storsenter, Ski', lat: 59.7178, lng: 10.8367 }
        ];

        // Search function
        function searchAddress() {
            const query = addressInput.value.trim().toLowerCase();
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            // Clear previous results
            resultsList.innerHTML = '';
            
            // Check for matches in Oslo areas first
            const areaMatches = osloAreas.filter(area => 
                area.name.toLowerCase().includes(query) || 
                area.address.toLowerCase().includes(query)
            );
            
            if (areaMatches.length > 0) {
                areaMatches.forEach(result => {
                    const li = document.createElement('li');
                    li.textContent = result.name + ' - ' + result.address;
                    
                    li.addEventListener('click', function() {
                        // Fill in address details
                        addressInput.value = result.address;
                        
                        // Set city if it exists in the address
                        if (result.address.includes('Oslo')) {
                            cityInput.value = 'Oslo';
                        } else if (result.address.includes('Sandvika')) {
                            cityInput.value = 'Sandvika';
                        } else if (result.address.includes('Lillestrøm')) {
                            cityInput.value = 'Lillestrøm';
                        } else if (result.address.includes('Jessheim')) {
                            cityInput.value = 'Jessheim';
                        } else if (result.address.includes('Ski')) {
                            cityInput.value = 'Ski';
                        }

                        // Set coordinates
                        document.getElementById('latitude').value = result.lat.toFixed(8);
                        document.getElementById('longitude').value = result.lng.toFixed(8);
                        
                        // Update map view and marker
                        map.setView([result.lat, result.lng], 16);
                        marker.setLatLng([result.lat, result.lng]);
                        
                        // Hide results
                        searchResults.style.display = 'none';
                    });
                    
                    resultsList.appendChild(li);
                });
                
                // Show results
                searchResults.style.display = 'block';
                return;
            }
            
            // If no matches in predefined areas, use OpenStreetMap Nominatim API
            // but focus search on Norway
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=no&limit=5`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        data.forEach(result => {
                            const li = document.createElement('li');
                            li.textContent = result.display_name;
                            
                            li.addEventListener('click', function() {
                                // Fill in address details
                                addressInput.value = result.display_name;
                                
                                // Try to extract city from address components
                                if (result.address) {
                                    cityInput.value = result.address.city || 
                                                      result.address.town || 
                                                      result.address.village || 
                                                      result.address.municipality || 
                                                      '';
                                }

                                // Set coordinates
                                const resultLat = parseFloat(result.lat);
                                const resultLng = parseFloat(result.lon);
                                
                                // Update inputs
                                document.getElementById('latitude').value = resultLat.toFixed(8);
                                document.getElementById('longitude').value = resultLng.toFixed(8);
                                
                                // Update map view and marker
                                map.setView([resultLat, resultLng], 16);
                                marker.setLatLng([resultLat, resultLng]);
                                
                                // Hide results
                                searchResults.style.display = 'none';
                            });
                            
                            resultsList.appendChild(li);
                        });
                        
                        // Show results
                        searchResults.style.display = 'block';
                    } else {
                        const li = document.createElement('li');
                        li.textContent = 'No results found';
                        resultsList.appendChild(li);
                        searchResults.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error searching for address:', error);
                    const li = document.createElement('li');
                    li.textContent = 'Error searching for address';
                    resultsList.appendChild(li);
                    searchResults.style.display = 'block';
                });
        }
    });
</script>
@endsection