@extends('layouts.app')

@section('title', 'User Details')

@section('content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">User Details: {{ $user->name }}</h1>
        <div>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary shadow-sm me-2">
                <i class="fas fa-edit fa-sm text-white-50 me-1"></i> Edit User
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Users
            </a>
        </div>
    </div>

    <div class="row">
        <!-- User Info Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">User ID:</div>
                        <div class="col-md-8">{{ $user->id }}</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Name:</div>
                        <div class="col-md-8">{{ $user->name }}</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Email:</div>
                        <div class="col-md-8">{{ $user->email }}</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Created At:</div>
                        <div class="col-md-8">{{ $user->created_at->format('F d, Y h:i A') }}</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-md-end fw-bold">Updated At:</div>
                        <div class="col-md-8">{{ $user->updated_at->format('F d, Y h:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Actions Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">User Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary mb-3">
                            <i class="fas fa-edit me-1"></i> Edit User Information
                        </a>
                        
                        @if(Auth::id() !== $user->id)
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                <i class="fas fa-trash me-1"></i> Delete User
                            </button>
                        @else
                            <button type="button" class="btn btn-danger" disabled>
                                <i class="fas fa-trash me-1"></i> Cannot Delete Your Own Account
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    @if(Auth::id() !== $user->id)
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete the user <strong>{{ $user->name }}</strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection