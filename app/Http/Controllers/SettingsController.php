<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();
        $editMode = $request->query('edit') === '1' || $request->session()->has('errors');

        return view('settings', [
            'profile' => $this->profilePayload($user),
            'editMode' => $editMode,
            'facultyPrograms' => config('faculties.programs', []),
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'birth_place_date' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'phone' => ['required', 'string', 'max:32'],
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];

        if ($user->role === 'lecturer') {
            $rules['nidn'] = ['required', 'string', 'max:32', Rule::unique('users', 'nidn')->ignore($user->id)];
        } else {
            $facultyPrograms = config('faculties.programs', []);
            $rules['nim'] = ['required', 'string', 'max:32', Rule::unique('users', 'nim')->ignore($user->id)];
            $rules['fakultas'] = ['required', 'string', Rule::in(array_keys($facultyPrograms))];
            $rules['jurusan'] = [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $facultyPrograms): void {
                    $programs = $facultyPrograms[$request->input('fakultas')] ?? [];
                    if (! in_array($value, $programs, true)) {
                        $fail('Study program is not valid for the selected faculty.');
                    }
                },
            ];
            $rules['batch_year'] = ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 1)];
        }

        $validated = $request->validate($rules);

        $user->full_name = $validated['full_name'];
        $user->name = $validated['full_name'];
        $user->email = $validated['email'];
        $user->birth_place_date = $validated['birth_place_date'];
        $user->address = $validated['address'];
        $user->phone = $validated['phone'];
        $user->gender = $validated['gender'];

        if ($user->role === 'lecturer') {
            $user->nidn = $validated['nidn'];
        } else {
            $user->nim = $validated['nim'];
            $user->fakultas = $validated['fakultas'];
            $user->jurusan = $validated['jurusan'];
            $user->batch_year = $validated['batch_year'];
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->profile_photo = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->updated_at = now();
        $user->save();

        return redirect()
            ->route('settings')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePayload(User $user): array
    {
        $displayName = trim($user->displayName()) !== '' ? $user->displayName() : (string) ($user->email ?? 'User');
        $words = preg_split('/\s+/', trim($displayName), -1, PREG_SPLIT_NO_EMPTY);
        $initials = 'U';

        if (count($words) >= 2) {
            $initials = strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
        } elseif (count($words) === 1) {
            $initials = strtoupper(substr($words[0], 0, 2));
        }

        $photoUrl = null;
        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            $photoUrl = asset('storage/'.$user->profile_photo);
        }

        return [
            'id' => $user->id,
            'full_name' => $user->full_name ?? $user->name ?? '',
            'username' => $user->username ?? '',
            'email' => $user->email ?? '',
            'role' => $user->role ?? 'student',
            'role_label' => ($user->role ?? 'student') === 'lecturer' ? 'Lecturer' : 'Student',
            'nim' => $user->nim,
            'nidn' => $user->nidn,
            'fakultas' => $user->fakultas,
            'jurusan' => $user->jurusan,
            'batch_year' => $user->batch_year,
            'birth_place_date' => $user->birth_place_date,
            'address' => $user->address,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'initials' => $initials,
            'photo_url' => $photoUrl,
            'is_lecturer' => ($user->role ?? 'student') === 'lecturer',
        ];
    }
}
