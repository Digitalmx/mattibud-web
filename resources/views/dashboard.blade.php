@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .dashboard-card {
        transition: transform .3s;
        border-radius: 0.75rem;
        overflow: hidden;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,.175);
    }
    .dashboard-stat-card {
        border: none;
        border-radius: 0.75rem;
        height: 100%;
    }
    .card-icon {
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
    }
    .quick-action-card {
        height: 100%;
        transition: all 0.3s;
    }
    .quick-action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }
    .btn-gradient {
        background: linear-gradient(45deg, #3a7bd5, #00d2ff);
        border: none;
        color: white;
    }
    .btn-gradient:hover {
        background: linear-gradient(45deg, #00d2ff, #3a7bd5);
        color: white;
    }
    .card-header {
        background-color: #fff;
        border-bottom: none;
    }
    .welcome-section {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        border-radius: 0.75rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
    }
    
    /* Responsive improvements */
    @media (max-width: 1199px) {
        .dashboard-stat-card .h3 {
            font-size: 1.5rem;
        }
        .card-icon {
            width: 54px;
            height: 54px;
        }
        .card-icon i {
            font-size: 1.5rem !important;
        }
    }
    
    @media (max-width: 991px) {
        .welcome-section {
            padding: 1.5rem;
        }
    }
    
    @media (max-width: 767px) {
        .welcome-section h2 {
            font-size: 1.75rem;
        }
        .welcome-section p {
            font-size: 1rem;
        }
        .dashboard-stat-card .h3 {
            font-size: 1.25rem;
        }
        .card-icon {
            width: 48px;
            height: 48px;
        }
        .card-icon i {
            font-size: 1.25rem !important;
        }
        .quick-action-icon {
            width: 60px !important;
            height: 60px !important;
        }
        .quick-action-icon i {
            font-size: 2.25rem !important;
        }
    }
    
    @media (max-width: 576px) {
        .welcome-section {
            padding: 1.25rem;
        }
        .card-title {
            font-size: 1.1rem;
        }
        .card-text {
            font-size: 0.9rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="welcome-section shadow">
        <h2 class="fw-bold mb-3">Welcome, {{ Auth::user()->name }}!</h2>
        <p class="lead mb-0">This is your admin dashboard where you can manage your website content and settings.</p>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4 border-0 dashboard-card">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">Dashboard Overview</h6>
                </div>
                <div class="card-body">                    
                    <div class="row g-3">
                        <!-- Dashboard Stats Cards -->
                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="card shadow h-100 dashboard-stat-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col">
                                            <div class="fw-bold text-primary text-uppercase mb-1 fs-6">
                                                Users</div>
                                            <div class="h3 mb-0 fw-bold text-gray-800">{{ \App\Models\User::count() }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="card-icon bg-primary bg-opacity-10">
                                                <i class="fas fa-users fa-2x text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stores Count Card -->
                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="card shadow h-100 dashboard-stat-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col">
                                            <div class="fw-bold text-success text-uppercase mb-1 fs-6">
                                                Stores</div>
                                            <div class="h3 mb-0 fw-bold text-gray-800">{{ \App\Models\Store::count() }}</div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="card-icon bg-success bg-opacity-10">
                                                <i class="fas fa-store fa-2x text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4 border-0 dashboard-card">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Store Management Card -->
                        <div class="col-xl-3 col-sm-6 mb-2">
                            <div class="card shadow-sm text-center quick-action-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="p-3 mb-3 bg-success bg-opacity-10 rounded-circle mx-auto quick-action-icon" style="width: 80px; height: 80px;">
                                        <i class="fas fa-store fa-3x text-success"></i>
                                    </div>
                                    <h5 class="card-title fw-bold">Manage Stores</h5>
                                    <p class="card-text flex-grow-1">Add, edit, or remove store locations and details.</p>
                                    <a href="{{ route('admin.stores.index') }}" class="btn btn-success">Manage Stores</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Stores Card -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4 border-0 dashboard-card">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">Recent Stores</h6>
                    <a href="{{ route('admin.stores.index') }}" class="btn btn-sm btn-primary">View All Stores</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>City</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(\App\Models\Store::orderBy('created_at', 'desc')->take(5)->get() as $store)
                                    <tr>
                                        <td>{{ $store->id }}</td>
                                        <td>{{ $store->name }}</td>
                                        <td>{{ $store->city ?: 'N/A' }}</td>
                                        <td>{{ $store->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.stores.show', $store) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No stores found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard loaded successfully');
        
        // Adjust heights of quick action cards to be equal in each row
        function adjustCardHeights() {
            // Reset heights first
            const cards = document.querySelectorAll('.quick-action-card');
            cards.forEach(card => {
                card.style.height = 'auto';
            });
            
            // Only equalize heights on larger screens
            if (window.innerWidth >= 768) {
                // Group cards by row
                const rows = {};
                cards.forEach(card => {
                    const rect = card.getBoundingClientRect();
                    const top = Math.round(rect.top);
                    if (!rows[top]) rows[top] = [];
                    rows[top].push(card);
                });
                
                // Set equal heights for each row
                Object.values(rows).forEach(rowCards => {
                    const maxHeight = Math.max(...rowCards.map(card => card.offsetHeight));
                    rowCards.forEach(card => {
                        card.style.height = `${maxHeight}px`;
                    });
                });
            }
        }
        
        // Run on load and resize
        adjustCardHeights();
        window.addEventListener('resize', adjustCardHeights);
    });
</script>
@endsection