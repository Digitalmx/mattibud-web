@extends('layouts.app')

@section('title', 'User Profile')

@section('styles')
<style>
    .profile-card {
        border-radius: 0.75rem;
        overflow: hidden;
        transition: all 0.3s;
        border: none;
    }
    .profile-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }
    .profile-header {
        background: linear-gradient(45deg, #4e73df, #224abe);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    .profile-img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 5px solid rgba(255,255,255,0.5);
        margin-bottom: 1rem;
    }
    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    .btn-primary:hover {
        background-color: #224abe;
        border-color: #224abe;
    }
    .form-label {
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">User Profile</h1>
        <a href="{{ route('dashboard') }}" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <!-- Profile Card -->
            <div class="card profile-card shadow mb-4">
                <div class="profile-header">
                    <img class="profile-img" src="https://ui-avatars.com/api/?name={{ $user->name }}&background=4e73df&color=ffffff&size=200" alt="{{ $user->name }}">
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="mb-0">{{ $user->email }}</p>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2"></i> Account Information</h6>
                    <div class="mb-3">
                        <div class="text-muted small">Account created on</div>
                        <div>{{ $user->created_at->format('F d, Y') }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Last update</div>
                        <div>{{ $user->updated_at->format('F d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-8 col-lg-7">
            <!-- Edit Profile Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Edit Profile</h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <hr class="mt-4 mb-4">
                        <h5 class="mb-3">Change Password</h5>
                        <p class="text-muted small mb-3">Leave the password fields empty if you don't want to change it.</p>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password">
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                <div class="form-text">Password must be at least 8 characters long.</div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection