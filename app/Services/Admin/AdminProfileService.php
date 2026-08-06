<?php

namespace App\Services\Admin;

use App\Models\Admin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AdminProfileService
{
    /*
    |--------------------------------------------------------------------------
    | Update Profile
    |--------------------------------------------------------------------------
    */

    public function updateProfile(
        Admin $admin,
        string $fullName,
        string $phoneNumber,
        ?UploadedFile $photo = null,
    ): Admin {

        if ($photo) {
            $this->deleteOldPhoto($admin);

            $path = $photo->store(
                'admins/profile',
                'public'
            );

            $admin->profile_photo = $path;
        }

        $admin->full_name = $fullName;
        $admin->phone_number = $phoneNumber;

        $admin->save();

        return $admin->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Change Password
    |--------------------------------------------------------------------------
    */

    public function changePassword(
        Admin $admin,
        string $currentPassword,
        string $newPassword,
    ): Admin {

        if (! Hash::check(
            $currentPassword,
            $admin->password
        )) {
            throw new RuntimeException(
                'Password lama tidak sesuai.'
            );
        }

        $admin->password = $newPassword;

        $admin->save();

        return $admin->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Old Photo
    |--------------------------------------------------------------------------
    */

    protected function deleteOldPhoto(Admin $admin): void
    {
        if (
            filled($admin->profile_photo)
            && Storage::disk('public')->exists(
                $admin->profile_photo
            )
        ) {
            Storage::disk('public')->delete(
                $admin->profile_photo
            );
        }
    }
}