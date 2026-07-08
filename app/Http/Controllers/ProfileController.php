<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // 1. Update User Basic Info
        $user->name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? $user->phone;

        if (isset($data['job_title'])) {
            $user->job_title = $data['job_title'];
        }

        // Bio mapping
        if (isset($data['bio'])) {
            $user->role_description = $data['bio'];
        }

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = hexdec(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('upload/user_images'), $filename);
            
            if ($user->photo && file_exists(public_path('upload/user_images/'.$user->photo))) {
                @unlink(public_path('upload/user_images/'.$user->photo));
            }
            $user->photo = $filename;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // 2. Sync with Employee Table if exists
        $employee = $user->employee;
        if ($employee) {
            $employee->update([
                'full_name'   => $user->name,
                'email'       => $user->email,
                'phone'       => $data['phone'] ?? $employee->phone,
                'designation' => $data['job_title'] ?? $employee->designation,
                'department'  => $data['department'] ?? $employee->department,
            ]);
        }

        return Redirect::route('profile-user')->with('success', 'Profile updated successfully across both systems.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
