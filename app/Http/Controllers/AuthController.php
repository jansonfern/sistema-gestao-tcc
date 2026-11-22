<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Notifications\PatientRegisteredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    /** Exibe o formulário de login, redireciona ao dashboard se já autenticado. */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /** Autentica por e-mail e senha, regenera a sessão e registra log de auditoria. */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if (config('security.log_security', true)) {
                Log::info('Login realizado com sucesso', [
                    'user_id' => Auth::id(),
                    'email' => $request->input('email'),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            return redirect()->intended(route('dashboard'));
        }

        if (config('security.log_failed_login', true)) {
            Log::warning('Tentativa de login falhou', [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    /** Exibe o formulário de solicitação de recuperação de senha. */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /** Envia link de redefinição de senha por e-mail via broker do Laravel. */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /** Exibe o formulário de redefinição de senha com o token recebido por e-mail. */
    public function showResetPasswordForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** Redefine a senha após validar o token e confirma o e-mail informado. */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make((string) $request->input('password')),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    /** Encerra a sessão, invalida o token CSRF e redireciona para a home. */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /** Exibe o formulário de autocadastro de paciente, redireciona se já autenticado. */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /** Registra paciente em transação (User + Patient), autentica e envia e-mail de boas-vindas. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'regex:/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'cpf' => 'required|string|size:14|unique:patients',
            'birth_date' => 'required|date|before:today',
            'phone' => ['required', 'string', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/', 'max:20'],
            'cep' => ['nullable', 'string', 'regex:/^(\d{8}|\d{5}-\d{3})$/'],
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|size:2',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'paciente',
            ]);

            Patient::create([
                'user_id' => $user->id,
                'cpf' => $data['cpf'],
                'birth_date' => $data['birth_date'],
                'phone' => $data['phone'],
                'cep' => $data['cep'] ?? null,
                'street' => $data['street'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
            ]);

            return $user;
        });

        Auth::login($user);

        try {
            $user->notify(new PatientRegisteredNotification);
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar e-mail de cadastro', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('dashboard')->with('success', 'Cadastro realizado com sucesso!');
    }
}
