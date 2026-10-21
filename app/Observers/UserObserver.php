<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        $roleValue = $user->role instanceof UserRole ? $user->role->value : $user->role;

        if ($roleValue === UserRole::Vendor->value) {
            if (! $user->vendor()->exists()) {
                $shopName = request('shop_name') ?: (! empty($user->username) ? "{$user->username}'s Shop" : 'New Vendor Shop');
                $shopDescription = request('shop_description');

                $user->vendor()->create([
                    'shop_name' => $shopName,
                    'shop_description' => $shopDescription,
                    'is_approved' => false,
                ]);
            }
        }
    }
}
