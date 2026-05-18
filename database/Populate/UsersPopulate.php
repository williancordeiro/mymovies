<?php

namespace Database\Populate;

use App\Models\User;

class UsersPopulate {

    public static function populate(): void {

        $user1 = new User([
            'username' => 'NormalUser1',
            'email' => 'example@email.com',
            'password' => 'password123',
            'role' => 'Default',
            'handle' => 'normaluser1' . mt_rand(1000, 9999),
            'avatar_file' => 'avatar.png'
        ]);

        if (!$user1->save()) {
            print_r($user1->errors());
            die("Erro ao salvar o usuário 1");
        };

        $user2 = new User([
            'username' => 'AdminUser1',
            'email' => 'admin@email.com',
            'password' => 'adminpass',
            'role' => 'Admin',
            'handle' => 'adminuser1' . mt_rand(1000, 9999),
            'avatar_file' => 'avatar.png'
        ]);

        if (!$user2->save()) {
            print_r($user2->errors());
            die("Erro ao salvar o usuário 2");
        };

        $user3 = new User([
            'username' => 'EditorUser3',
            'email' => 'editor@email.com',
            'password' => 'editorpass',
            'role' => 'Admin',
            'handle' => 'editoruser3' . mt_rand(1000, 9999),
            'avatar_file' => 'avatar.png'
        ]);

        if (!$user3->save()) {
            print_r($user3->errors());
            die("Erro ao salvar o usuário 3");
        };        


        echo "Users populated successfully.\n";
    }
}