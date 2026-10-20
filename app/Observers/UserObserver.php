<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $roleValue = $user->role instanceof UserRole ? $user->role->value : $user->role;

        if ($roleValue === UserRole::Vendor->value) {
            if (! $user->vendor()->exists()) {
                $shopName = ! empty($user->username) ? "{$user->username}'s Shop" : 'New Vendor Shop';

                $user->vendor()->create([
                    'shop_name' => $shopName,
                    'shop_description' => null,
                    'is_approved' => false,
                ]);
            }
        }
    }
}
