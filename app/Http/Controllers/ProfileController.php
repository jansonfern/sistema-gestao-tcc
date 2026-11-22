<?php

namespace App\Http\Controllers;

use App\Notifications\ProfileUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $phone = null;
        $crm = null;
        $cpf = null;
        $birthDate = null;

        if ($user->role === 'paciente' && $user->patient) {
            $phone = $user->patient->phone;
            $cpf = $user->patient->cpf;
            $birthDate = $user->patient->birth_date;
        } elseif ($user->role === 'medico' && $user->doctor) {
            $phone = $user->doctor->phone ?? '';
            $crm = $user->doctor->crm;
        }

        return view('profile.edit', compact('user', 'phone', 'crm', 'cpf', 'birthDate'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'regex:/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/', 'max:20'],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $oldEmail = $user->email;

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password']) && ! Hash::check($data['password'], $user->password)) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $userChanged = $user->wasChanged();
        $emailChanged = $user->wasChanged('email');

        $profileChanged = $userChanged;

        if ($user->role === 'paciente' && $user->patient) {
            $user->patient->phone = $data['phone'];
            $user->patient->save();
            $profileChanged = $profileChanged || $user->patient->wasChanged();
        } elseif ($user->role === 'medico' && $user->doctor) {
            $user->doctor->phone = $data['phone'];
            $user->doctor->save();
            $profileChanged = $profileChanged || $user->doctor->wasChanged();
        }

        if ($profileChanged) {
            try {
                $user->notify(new ProfileUpdatedNotification);

                if ($emailChanged) {
                    Notification::route('mail', $oldEmail)->notify(new ProfileUpdatedNotification);
                }
            } catch (\Throwable $e) {
                Log::warning('Falha ao enviar e-mail de atualização de perfil', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('profile.edit')->with('success', 'Dados atualizados com sucesso!');
    }
}
