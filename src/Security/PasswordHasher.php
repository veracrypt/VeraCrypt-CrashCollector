<?php

namespace Veracrypt\CrashCollector\Security;

use Veracrypt\CrashCollector\Exception\ConfigurationException;
use Veracrypt\CrashCollector\Exception\InvalidPasswordException;

class PasswordHasher
{
    public const MAX_PASSWORD_LENGTH = 4096;

    protected string $algorithm = \PASSWORD_DEFAULT;
    protected array $options;

    /**
     * @throws ConfigurationException
     */
    public function __construct()
    {
        $algorithm = @$_ENV['PWD_HASH_ALGORITHM'];
        if ('' != $algorithm) {
            $algorithms = ['2y' => \PASSWORD_BCRYPT];
            if (\defined('PASSWORD_ARGON2I')) {
                $algorithms['argon2i'] = \PASSWORD_ARGON2I;
            }
            if (\defined('PASSWORD_ARGON2ID')) {
                $algorithms['argon2id'] = \PASSWORD_ARGON2ID;
            }
            if (!isset($algorithms[$algorithm])) {
                throw new ConfigurationException("Unsupported PWD_HASH_ALGORITHM: '$algorithm'");
            }
            $this->algorithm = $algorithms[$algorithm];
        }
        $cost = max((int)@$_ENV['PWD_HASH_COST'], 13);
        if (31 < $cost) {
            throw new ConfigurationException('PWD_HASH_COST must be in the range of 4-31');
        }
        $opsLimit = max((int)@$_ENV['PWD_HASH_OPSLIMIT'], 4, defined('SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE') ? \SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE : 4);
        $memLimit = max((int)@$_ENV['PWD_HASH_MEMLIMIT'], 64 * 1024 * 1024, defined('SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE') ? \SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE : 64 * 1024 * 1024);

        $this->options = [
            'cost' => $cost,
            'time_cost' => $opsLimit,
            'memory_cost' => $memLimit >> 10,
            'threads' => 1,
        ];
    }

    /**
     * @todo add pepper? see comment at https://www.php.net/manual/en/function.password-hash.php#124138
     * @throws InvalidPasswordException
     */
    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        if (static::MAX_PASSWORD_LENGTH < strlen($plainPassword)) {
            throw new InvalidPasswordException();
        }

        if (\PASSWORD_BCRYPT === $this->algorithm && (72 < strlen($plainPassword) || str_contains($plainPassword, "\0"))) {
            $plainPassword = base64_encode(hash('sha512', $plainPassword, true));
        }

        return password_hash($plainPassword, $this->algorithm, $this->options);
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        if ('' === $plainPassword || static::MAX_PASSWORD_LENGTH < strlen($plainPassword)) {
            return false;
        }

        if (!str_starts_with($hashedPassword, '$argon')) {
            // Bcrypt cuts on NUL chars and after 72 bytes
            if (str_starts_with($hashedPassword, '$2') && (72 < \strlen($plainPassword) || str_contains($plainPassword, "\0"))) {
                $plainPassword = base64_encode(hash('sha512', $plainPassword, true));
            }

            return password_verify($plainPassword, $hashedPassword);
        }

        if (\extension_loaded('sodium') && version_compare(\SODIUM_LIBRARY_VERSION, '1.0.14', '>=')) {
            return sodium_crypto_pwhash_str_verify($hashedPassword, $plainPassword);
        }

        if (\extension_loaded('libsodium') && version_compare(phpversion('libsodium'), '1.0.14', '>=')) {
            return \Sodium\crypto_pwhash_str_verify($hashedPassword, $plainPassword);
        }

        return password_verify($plainPassword, $hashedPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return password_needs_rehash($hashedPassword, $this->algorithm, $this->options);
    }
}
