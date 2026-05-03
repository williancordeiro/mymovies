<?php

namespace Database\Populate;

use App\Models\User;

class UsersPopulate {

    public static function populate(): void {

        $user1 = new User([
            'username' => 'NormalUser1',
            'email' => 'example@email.com',
            'encrypted_password' => password_hash('password123', PASSWORD_DEFAULT),
            'admin' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $user1->save();

        $user2 = new User([
            'username' => 'AdminUser1',
            'email' => 'admin@email.com',
            'encrypted_password' => password_hash('adminpass', PASSWORD_DEFAULT),
            'admin' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $user2->save();


        echo "Users populated successfully.\n";
    }
}