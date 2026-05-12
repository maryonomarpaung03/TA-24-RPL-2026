<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Schema::hasColumn('users', 'name') && ! Schema::hasColumn('users', 'full_name')) {
            abort(500, 'Tabel users harus memiliki kolom name atau full_name.');
        }

        $data = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (Schema::hasColumn('users', 'name')) {
            $data['name'] = $validated['full_name'];
        }

        if (Schema::hasColumn('users', 'full_name')) {
            $data['full_name'] = $validated['full_name'];
        }

        $user = User::create($data);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
