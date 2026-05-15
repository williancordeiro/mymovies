<?php

namespace Database\Populate;

use App\Models\User;

class UsersPopulate {

    public static function populate(): void {

        $user1 = new User([
            'username' => 'NormalUser1',
            'email' => 'example@email.com',
            'encrypted_password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'Default',
            'handle' => 'normaluser1' . mt_rand(1000, 9999),
            'avatar_file' => 'avatar.png'
        ]);

        $user1->save();

        $user2 = new User([
            'username' => 'AdminUser1',
            'email' => 'admin@email.com',
            'encrypted_password' => password_hash('adminpass', PASSWORD_DEFAULT),
            'role' => 'Admin',
            'handle' => 'adminuser1' . mt_rand(1000, 9999),
            'avatar_file' => 'avatar.png'
        ]);

        $user2->save();

        $user3 = new User([
            'username' => 'EditorUser3',
            'email' => 'editor@email.com',
            'encrypted_password' => password_hash('editorpass', PASSWORD_DEFAULT),
            'role' => 'Admin',
            'handle' => 'editoruser3' . mt_rand(1000, 9999),
            'avatar_file' => 'avatar.png'
        ]);

        $user3->save();        


        echo "Users populated successfully.\n";
    }
}