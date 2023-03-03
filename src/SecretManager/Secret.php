<?php

namespace YorCreative\Scrubber\SecretManager;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Config;

class Secret
{
    protected string $key;

    protected string $variable;

    public function __construct(string $key, string $variable)
    {
        $this->key = $key;
        $this->variable = self::getEncrypter()->encryptString($variable);
    }

    private static function getEncrypter(): Encrypter
    {
        return new Encrypter(
            Config::get('scrubber.secret_manager.key'),
            Config::get('scrubber.secret_manager.cipher')
        );
    }

    public static function decrypt(string $encryptedSecret): string
    {
        return self::getEncrypter()->decryptString($encryptedSecret);
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
