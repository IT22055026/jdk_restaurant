<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restaurant BYOB - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .login-wrapper {
            width: 100%;
            height: 100vh;
            margin: 0;
            padding: 0;
        }

        .login-container {
            background: white;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 100vh;
            width: 100%;
        }

        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            .image-section {
                display: none !important;
            }
        }

        .image-section {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .form-section {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        @media (max-width: 640px) {
            .form-section {
                padding: 40px 24px;
            }
        }

        .logo-mobile {
            display: none;
            gap: 12px;
            margin-bottom: 30px;
            align-items: center;
        }

        @media (max-width: 768px) {
            .logo-mobile {
                display: flex;
            }
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        h1 {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            background: white;
        }

        .input-wrapper:focus-within {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .input-wrapper i {
            color: #9ca3af;
            margin-right: 12px;
            width: 16px;
            text-align: center;
        }

        .input-wrapper input {
            flex: 1;
            border: none;
            background: transparent;
            outline: none;
            font-size: 14px;
            color: #111827;
        }

        .input-wrapper input::placeholder {
            color: #d1d5db;
        }

        .form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #dc2626;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            color: #374151;
        }

        .forgot-link {
            color: #6b7280;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #dc2626;
        }

        .submit-btn {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-btn:hover {
            box-shadow: 0 12px 32px rgba(220, 38, 38, 0.4);
            transform: translateY(-2px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .demo-box {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }

        .demo-title {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-item {
            font-size: 13px;
            margin-bottom: 10px;
            color: #4b5563;
        }

        .demo-item i {
            color: #dc2626;
            margin-right: 8px;
            width: 14px;
            text-align: center;
        }

        .demo-item strong {
            color: #1f2937;
        }

        .demo-password {
            font-size: 11px;
            color: #9ca3af;
            margin-left: 22px;
            margin-top: 4px;
        }

        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
        }

        .error-box i {
            color: #dc2626;
            margin-top: 2px;
        }

        .error-content {
            flex: 1;
        }

        .error-content p:first-child {
            font-size: 14px;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 6px;
        }

        .error-content p {
            font-size: 12px;
            color: #dc2626;
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <!-- Image Section -->
            <div class="image-section">
                @if (file_exists(public_path('images/login.jpg')))
                    <img src="{{ asset('images/login.jpg') }}" alt="Restaurant Food">
                @else
                    <div style="text-align: center; color: #9ca3af;">
                        <i class="fas fa-image" style="font-size: 60px; opacity: 0.3; margin-bottom: 20px; display: block;"></i>
                        <h3 style="color: #4b5563; font-size: 18px; margin-bottom: 12px;">Place Your Restaurant Image</h3>
                        <p style="color: #9ca3af; font-size: 13px; max-width: 280px;">
                            Add image to: <code style="background: white; padding: 4px 8px; border-radius: 4px; display: block; margin-top: 12px;">public/images/login.jpg</code>
                        </p>
                    </div>
                @endif
            </div>

            <!-- Form Section -->
            <div class="form-section">
                <!-- Mobile Logo -->
                <div class="logo-mobile">
                    <div class="logo-icon">
                        <i class="fas fa-pizza-slice"></i>
                    </div>
                    <div>
                        <div style="font-size: 20px; font-weight: 700; color: #1f2937;">Piznek</div>
                        <div style="font-size: 12px; color: #dc2626; font-weight: 600;">POS System</div>
                    </div>
                </div>

                <h1>Welcome back!</h1>
                <p class="subtitle">Please enter your details to access the system</p>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="error-box">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="error-content">
                            <p>Login failed</p>
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="admin@restaurant.local"
                                required
                            >
                        </div>
                        @error('email')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                required
                            >
                        </div>
                        @error('password')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="form-footer">
                        <div class="checkbox-group">
                            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <!-- Demo Credentials -->
                <div class="demo-box">
                    <div class="demo-title">Demo Credentials</div>

                    <div class="demo-item">
                        <i class="fas fa-user-shield"></i>
                        <strong>Admin:</strong> admin@restaurant.local
                        <div class="demo-password">Password: password</div>
                    </div>

                    <div class="demo-item">
                        <i class="fas fa-user-tie"></i>
                        <strong>Manager:</strong> manager@restaurant.local
                        <div class="demo-password">Password: password</div>
                    </div>

                    <div class="demo-item">
                        <i class="fas fa-user"></i>
                        <strong>Cashier:</strong> cashier@restaurant.local
                        <div class="demo-password">Password: password</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
