<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function create(array $data): User
    {
        $branchId = $data['role'] === User::ROLE_ADMIN ? null : $data['branch_id'];

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'branch_id' => $branchId,
        ]);
        $user->disableLogging()->save();

        $user->assignRole($data['role']);

        activity()
            ->performedOn($user)
            ->event('created')
            ->log("Корбар эҷод шуд: {$user->name} ({$user->email}), нақш: {$data['role']}");

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'branch_id' => $data['role'] === User::ROLE_ADMIN ? null : $data['branch_id'],
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->disableLogging()->update($updateData);

        $user->syncRoles([$data['role']]);

        activity()
            ->performedOn($user)
            ->event('updated')
            ->log("Корбар навсозӣ шуд: {$user->name} ({$user->email}), нақш: {$data['role']}");

        return $user;
    }

    public function delete(User $user): void
    {
        $name = $user->name;
        $email = $user->email;

        activity()
            ->performedOn($user)
            ->event('deleted')
            ->log("Корбар нест карда шуд: {$name} ({$email})");

        $user->disableLogging()->delete();
    }
}
