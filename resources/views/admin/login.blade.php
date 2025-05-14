@extends('admin.layouts.app')

@section('title', 'Admin Login')

@section('styles')
<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        padding: 1.5rem 0;
    }
    .login-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }
    .login-card .card-header {
        background: linear-gradient(45deg, #4e73df, #224abe);
        border-bottom: none;
        padding: 1.5rem;
    }
    .login-card .card-body {
        padding: 2rem;
    }
    .login-logo {
        width: 80px;
        height: 80px;
        background-color: white;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 1rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .login-logo i {
        font-size: 2.5rem;
        color: #4e73df;
    }
    .form-control {
        height: calc(2.5rem + 2px);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        background-color: #f8f9fc;
        border: 1px solid #d1d3e2;
    }
    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }
    .login-btn {
        padding: 0.75rem;
        font-weight: 600;
        border-radius: 0.5rem;
        background: linear-gradient(45deg, #4e73df, #224abe);
        border: none;
    }
    .login-btn:hover {
        background: linear-gradient(45deg, #224abe, #4e73df);
    }
    .input-group-text {
        background-color: #4e73df;
        color: white;
        border: none;
        border-radius: 0.5rem 0 0 0.5rem;
    }
    .form-floating > .form-control {
        height: calc(3.5rem + 2px);
        line-height: 1.25;
    }
    .form-floating > label {
        padding: 1rem;
    }
    .form-check-input:checked {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    
    /* Responsive improvements */
    @media (max-width: 991.98px) {
        .login-logo {
            width: 70px;
            height: 70px;
        }
        .login-logo i {
            font-size: 2.25rem;
        }
    }
    
    @media (max-width: 767.98px) {
        .login-card .card-body {
            padding: 1.5rem;
        }
        .login-card .card-header {
            padding: 1.25rem;
        }
    }
    
    @media (max-width: 575.98px) {
        .container.login-container {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .login-logo {
            width: 60px;
            height: 60px;
        }
        .login-logo i {
            font-size: 2rem;
        }
        .login-card .card-body {
            padding: 1.25rem;
        }
        .login-card .card-header {
            padding: 1rem;
        }
        .text-center.mb-4 h2 {
            font-size: 1.5rem;
        }
        .text-center.mb-4 p {
            font-size: 0.9rem;
        }
        .card-header h4 {
            font-size: 1.25rem;
        }
        .form-floating > .form-control {
            height: calc(3.25rem + 2px);
        }
    }
</style>
@endsection

@section('content')
<div class="container login-container">
    <div class="row justify-content-center w-100">
        <div class="col-lg-5 col-md-7 col-sm-10 col-12">
            <div class="text-center mb-4">
                <h2 class="text-white fw-bold">Admin Dashboard</h2>
                <p class="text-white mb-0">Login to access your control panel</p>
            </div>
            <div class="card login-card">
                <div class="card-header text-center text-white">
                    <div class="login-logo">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h4 class="mb-0 fw-bold">Admin Login</h4>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.submit') }}">
                        @csrf
                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <div class="form-floating flex-grow-1">
                                    <input type="email" class="form-control border-start-0" id="email" name="email" 
                                           value="{{ old('email') }}" placeholder="Email Address" required autofocus>
                                    <label for="email">Email Address</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <div class="form-floating flex-grow-1">
                                    <input type="password" class="form-control border-start-0" id="password" 
                                           name="password" placeholder="Password" required>
                                    <label for="password">Password</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg login-btn">
                                <i class="fas fa-sign-in-alt me-2"></i> Sign In
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-4">
                <p class="text-white mb-0">&copy; {{date('Y')}} Your Company. All rights reserved.</p>
            </div>
        </div>
    </div>
</div>
@endsection