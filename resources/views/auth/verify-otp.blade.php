<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Team Tasker</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #6366f1;
            --accent: #8b5cf6;
            --bg-dark: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
            background-image:
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(139, 92, 246, 0.15) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow-x: hidden;
        }

        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.2;
            animation: move 20s infinite alternate;
        }

        @keyframes move {
            from {
                transform: translate(-10%, -10%);
            }

            to {
                transform: translate(10%, 10%);
            }
        }

        .auth-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-area {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.5);
        }

        .auth-title {
            color: white;
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .form-label {
            color: #cbd5e1;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: white;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            color: white;
        }

        .btn-auth {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            color: white;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
            opacity: 0.9;
        }

        .auth-footer {
            margin-top: 2rem;
            text-align: center;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .auth-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-link:hover {
            color: var(--accent);
        }

        .alert-premium {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border-radius: 12px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="blob"></div>

    <div class="container d-flex justify-content-center">
        <div class="auth-card">
            <div class="logo-area">
                <div class="logo-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="auth-title">Verify Email</h1>
                <p class="auth-subtitle">We have sent a 6-digit OTP to your email.</p>
            </div>

            @if (session('error'))
                <div class="alert alert-premium mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success mb-4 bg-success bg-opacity-10 border-success border-opacity-20 text-success"
                    style="border-radius: 12px; font-size: 0.85rem;">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('verifyOtp') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ request('email') }}">
                <div class="mb-4">
                    <label for="otp" class="form-label">Enter OTP</label>
                    <input type="text" id="otp" name="otp" class="form-control text-center"
                        placeholder="123456" maxlength="6" required style="letter-spacing: 5px; font-size: 1.2rem; font-weight: bold;">
                </div>

                <button type="submit" class="btn btn-auth">
                    Verify & Continue <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>

            <div class="auth-footer d-flex flex-column align-items-center gap-2 mt-4">
                <span>Didn't receive the code?</span>
                <form action="{{ route('resendOtp') }}" method="POST">
                    @csrf
                    <input type="hidden" name="email" value="{{ request('email') }}">
                    <button type="submit" class="btn btn-link auth-link p-0 m-0" style="text-decoration: none; border: none; background: none;">Resend OTP</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
