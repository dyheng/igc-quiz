<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetAdminPassword extends Command
{
    protected $signature = 'admin:set-password {password? : Plain-text password (will be prompted if omitted)}';

    protected $description = 'Set the admin password (stored as bcrypt hash in .env as ADMIN_PASSWORD_HASH)';

    public function handle(): int
    {
        $password = $this->argument('password') ?: $this->secret('Enter new admin password');

        if (! is_string($password) || strlen($password) < 4) {
            $this->error('Password must be at least 4 characters.');

            return self::FAILURE;
        }

        $hash = Hash::make($password);

        $envPath = base_path('.env');

        if (! is_writable($envPath)) {
            $this->error('.env file is not writable.');

            return self::FAILURE;
        }

        $contents = file_get_contents($envPath);

        // Use single quotes so $-prefixed bcrypt hashes are not interpreted
        // by phpdotenv as variable references ($2y$, $12$, etc.).
        $line = "ADMIN_PASSWORD_HASH='" . $hash . "'";

        if (preg_match('/^ADMIN_PASSWORD_HASH=.*$/m', $contents)) {
            // Use callback to avoid preg_replace interpreting $2, $12 in the
            // bcrypt hash as backreferences.
            $contents = preg_replace_callback(
                '/^ADMIN_PASSWORD_HASH=.*$/m',
                fn () => $line,
                $contents
            );
        } else {
            $contents .= PHP_EOL . $line . PHP_EOL;
        }

        file_put_contents($envPath, $contents);

        $this->info('Admin password updated successfully.');
        $this->line('Tip: run "php artisan config:clear" if config is cached.');

        return self::SUCCESS;
    }
}
