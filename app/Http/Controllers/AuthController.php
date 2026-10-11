<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginPage()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }

        if (Auth::check()) {
            $user = Auth::user();

            return redirect()->route(Auth::guard('client')->check() ? 'client.dashboard' : 'dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // First attempt as Super Admin using 'admin' guard
        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        // Attempt as Client using 'client' guard
        if (Auth::guard('client')->attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            if (! Auth::guard('client')->user()->is_approved) {
                Auth::guard('client')->logout();

                return redirect()->back()->withInput()->with('error', 'Your account is pending admin approval.');
            }
            $request->session()->regenerate();

            return redirect()->route('client.dashboard');
        }

        // Attempt as normal User using 'web' guard
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        if (! $user->is_approved) {
            return redirect()->back()->withInput()->with('error', 'Your account is pending admin approval.');
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function sendOtp($user)
    {
        $otp = rand(100000, 999999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            \Illuminate\Support\Facades\Mail::raw('Your OTP is: '.$otp, function ($message) use ($user) {
                $message->to($user->email)->subject('Verify your Email OTP');
            });

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send OTP to: '.$user->email.' - '.$e->getMessage());

            return false;
        }
    }

    public function registerPage()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::guard('client')->check() ? 'client.dashboard' : 'dashboard');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users|unique:clients',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => 'This email already exists in our system.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => null, // Admin will assign role upon approval
            'is_approved' => false,
        ]);

        $mailSent = $this->sendOtp($user);

        if (! $mailSent) {
            $user->update([
                'email_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            return redirect()->route('loginPage')->with('success', 'Registration successful! Please wait for admin approval before logging in.');
        }

        return redirect()->route('verifyOtpPage', ['email' => $user->email])->with('success', 'Registration successful! Please verify your email with the OTP sent.');
    }

    public function clientRegisterPage()
    {
        if (Auth::check()) {
            $user = Auth::user();

            return redirect()->route(Auth::guard('client')->check() ? 'client.dashboard' : 'dashboard');
        }

        return view('auth.client-register');
    }

    public function clientRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => 'This email already exists in our system.',
        ]);

        $user = \App\Models\Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'is_approved' => false, // Requires admin approval
            'status' => 'active',
        ]);

        $mailSent = $this->sendOtp($user);

        if (! $mailSent) {
            $user->update([
                'email_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            return redirect()->route('loginPage')->with('success', 'Client registration successful! Please wait for admin approval before logging in.');
        }

        return redirect()->route('verifyOtpPage', ['email' => $user->email])->with('success', 'Client registration successful! Please verify your email with the OTP sent.');
    }

    public function verifyOtpPage(Request $request)
    {
        return view('auth.verify-otp', ['email' => $request->email]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();
        $isClient = false;

        if (! $user) {
            $user = \App\Models\Client::where('email', $request->email)->first();
            $isClient = true;
        }

        if (! $user) {
            return back()->with('error', 'User not found.');
        }

        if (! $user->otp || (string) $user->otp !== (string) $request->otp) {
            return back()->with('error', 'Invalid OTP.');
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return back()->with('error', 'OTP has expired. Please request a new one.');
        }

        $user->update([
            'email_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        return redirect()->route('loginPage')->with('success', 'Email verified successfully! Please wait for admin approval before logging in.');
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            $user = \App\Models\Client::where('email', $request->email)->first();
        }

        if (! $user) {
            return back()->with('error', 'User not found.');
        }

        if ($user->email_verified_at) {
            return redirect()->route('loginPage')->with('success', 'Email is already verified.');
        }

        $mailSent = $this->sendOtp($user);

        if (! $mailSent) {
            $user->update([
                'email_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            return redirect()->route('loginPage')->with('success', 'Email verified automatically (SMTP not configured). Please wait for admin approval before logging in.');
        }

        return back()->with('success', 'A new OTP has been sent to your email.');
    }

    public function logout()
    {
        Auth::guard('admin')->logout();
        Auth::guard('client')->logout();
        Auth::logout();

        return to_route('loginPage');
    }

    public function forgotPasswordPage()
    {
        return view('auth.forgot-password');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        // In a real app, we would send an email with a token.
        // For this demo/requirement, we'll redirect directly to a reset page with the email.
        return redirect()->route('resetPasswordPage', ['email' => $request->email])
            ->with('success', 'Reset link sent! (Simulated: Redirected to reset page)');
    }

    public function resetPasswordPage(Request $request)
    {
        return view('auth.reset-password', ['email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('loginPage')->with('success', 'Password reset successfully! You can now log in.');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id.'|unique:clients',
            'password' => 'nullable|string|min:8',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
            $data['profile_image'] = $path;
        }

        $user->update($data);

        return back()->with('success', 'Profile updated successfully!');
    }
}
