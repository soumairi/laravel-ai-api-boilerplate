<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private static ?string $keyDirectory = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => config('app.key') ?: 'base64:'.base64_encode(random_bytes(32)),
            'jwt.algo' => 'RS256',
            'jwt.keys.public' => 'file://'.$this->jwtKeyDirectory().'/public.pem',
            'jwt.keys.private' => 'file://'.$this->jwtKeyDirectory().'/private.pem',
            'jwt.keys.passphrase' => null,
        ]);
        $testAddress = implode(':', str_split(bin2hex(random_bytes(8)), 4));
        $this->withServerVariables(['REMOTE_ADDR' => "2001:db8::$testAddress"]);
    }

    protected function jwtKeyDirectory(): string
    {
        if (self::$keyDirectory !== null) {
            return self::$keyDirectory;
        }

        $directory = sys_get_temp_dir().'/laravel-api-tests-'.bin2hex(random_bytes(8));
        if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the temporary JWT key directory.');
        }

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($key === false || ! openssl_pkey_export($key, $private)) {
            throw new RuntimeException('Unable to generate an RSA test key.');
        }

        $details = openssl_pkey_get_details($key);
        if ($details === false) {
            throw new RuntimeException('Unable to read the RSA test public key.');
        }

        file_put_contents($directory.'/private.pem', $private);
        file_put_contents($directory.'/public.pem', $details['key']);
        chmod($directory.'/private.pem', 0600);
        chmod($directory.'/public.pem', 0644);

        self::$keyDirectory = $directory;
        register_shutdown_function(static function () use ($directory): void {
            @unlink($directory.'/private.pem');
            @unlink($directory.'/public.pem');
            @rmdir($directory);
        });

        return $directory;
    }
}
