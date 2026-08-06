<?php

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfileController extends AdminController
{
    public function __construct(
        protected AdminProfileService $profileService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $admin = auth()->user();

        return view(
            'admin.modules.profile.index',
            compact('admin')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Profile
    |--------------------------------------------------------------------------
    */

    public function update(Request $request)
    {
        $admin = auth()->user();

        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:100',
            ],

            'phone_number' => [
                'required',
                'string',
                'max:15',
            ],

            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $this->profileService->updateProfile(
            admin: $admin,
            fullName: $validated['full_name'],
            phoneNumber: $validated['phone_number'],
            photo: $request->file('profile_photo'),
        );

        return $this->success(
            'admin.profile.index',
            'Profile berhasil diperbarui.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Change Password
    |--------------------------------------------------------------------------
    */

    public function password(Request $request)
    {
        $admin = auth()->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        try {

            $this->profileService->changePassword(
                admin: $admin,
                currentPassword: $validated['current_password'],
                newPassword: $validated['new_password'],
            );

        } catch (RuntimeException $e) {

            return $this->error(
                $e->getMessage()
            );
        }

        return $this->success(
            'admin.profile.index',
            'Password berhasil diperbarui.'
        );
    }
}