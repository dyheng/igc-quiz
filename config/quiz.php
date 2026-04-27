<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Password Hash
    |--------------------------------------------------------------------------
    |
    | A bcrypt hash of the admin password. Set with:
    |   php artisan admin:set-password <password>
    | which writes ADMIN_PASSWORD_HASH= to your .env file.
    |
    */
    'admin_password_hash' => env('ADMIN_PASSWORD_HASH', ''),

    /*
    |--------------------------------------------------------------------------
    | Session Key
    |--------------------------------------------------------------------------
    |
    | Session key used to mark an authenticated admin.
    |
    */
    'admin_session_key' => 'is_admin',
];
