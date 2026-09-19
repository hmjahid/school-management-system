<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = Auth::user();
        $profile = $user->hasRole('student')
            ? $user->student()->first()
            : $user->guardian()->first();

        return view('profile.edit', compact('user', 'profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('profile-photos', 'public');
        }

        $password = $validated['password'] ?? null;
        unset($validated['password'], $validated['current_password']);

        $user->fill($validated);

        if ($password) {
            $user->password = Hash::make($password);
        }

        $user->save();

        $profile = $user->hasRole('student')
            ? $user->student()->first()
            : $user->guardian()->first();

        if ($profile) {
            $profileFillable = collect($profile->getFillable())->except(['user_id'])->toArray();
            $profileData = array_intersect_key($validated, array_flip($profileFillable));
            if ($profileData) {
                $profile->fill($profileData)->save();
            }
        }

        return back()->with('status', __('Profile updated successfully.'));
    }
}
