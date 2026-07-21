<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

final class BoilerplateInitCommand extends Command
{
    protected $signature = 'boilerplate:init
        {--force : Allow initialization in production and replace JWT keys}
        {--skip-migrate : Do not run database migrations}
        {--skip-seed : Do not run database seeders}';

    protected $description = 'Initialize the environment, application key, JWT keys, and database';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        if ($this->laravel->isProduction() && ! $force) {
            $this->error('Refusing production initialization without --force.');

            return self::FAILURE;
        }

        try {
            $this->ensureEnvironmentFile();
            $this->ensureApplicationKey($force);
            $this->generateJwtKeys($force);
            DB::select('select 1');
            Redis::connection()->ping();

            if (! $this->option('skip-migrate')) {
                $this->call('migrate', ['--force' => true]);
            }
            if (! $this->option('skip-seed')) {
                $this->call('db:seed', ['--force' => true]);
            }
            $this->call('scramble:export', ['--path' => 'docs/openapi/openapi.json']);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        Artisan::call('optimize:clear');
        $this->components->info('Boilerplate initialized successfully.');

        return self::SUCCESS;
    }

    private function ensureEnvironmentFile(): void
    {
        $environment = base_path('.env');
        if (File::exists($environment)) {
            return;
        }

        $example = base_path('.env.example');
        if (! File::exists($example)) {
            throw new RuntimeException('.env.example is missing.');
        }

        File::copy($example, $environment);
        $this->components->info('Created .env from .env.example.');
    }

    private function ensureApplicationKey(bool $force): void
    {
        if (filled(config('app.key'))) {
            $this->components->info('Application key is already configured.');

            return;
        }

        $status = $this->call('key:generate', ['--force' => $force]);
        if ($status !== self::SUCCESS) {
            throw new RuntimeException('Unable to generate the application key.');
        }
    }

    private function generateJwtKeys(bool $force): void
    {
        $directory = storage_path('keys');
        $privatePath = $directory.'/jwt-private.pem';
        $publicPath = $directory.'/jwt-public.pem';

        if (! $force && (File::exists($privatePath) || File::exists($publicPath))) {
            $this->components->warn('JWT keys already exist; leaving them unchanged.');

            return;
        }

        File::ensureDirectoryExists($directory, 0700);
        $key = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($key === false) {
            throw new RuntimeException('Unable to create the RSA private key.');
        }

        if (! openssl_pkey_export($key, $privatePem)) {
            throw new RuntimeException('Unable to export the RSA private key.');
        }
        $details = openssl_pkey_get_details($key);
        if ($details === false || ! isset($details['key'])) {
            throw new RuntimeException('Unable to derive the RSA public key.');
        }

        File::put($privatePath, $privatePem, true);
        File::put($publicPath, $details['key'], true);
        chmod($privatePath, 0600);
        chmod($publicPath, 0644);
        $this->components->info('Generated RS256 JWT key pair.');
    }
}
