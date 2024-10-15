<?php

namespace Veracrypt\CrashCollector\Security;

use Veracrypt\CrashCollector\Exception\AntiCSRFException;
use Veracrypt\CrashCollector\Exception\TokenHashMismatchException;
use Veracrypt\CrashCollector\Exception\TokenHasInvalidFormatException;
use Veracrypt\CrashCollector\Exception\TokenIndexNotInSessionException;
use Veracrypt\CrashCollector\Exception\TokenIsMissingException;
use Veracrypt\CrashCollector\Exception\TokenMatchesWrongFormException;

/**
 * Design inspired by paragonie/anti-csrf.
 *
 * @todo we only need the 'storage' part of the Session, really. However it would need a bit of refactoring to disentangle
 *       the Storage-session from the Security-session while making sure that 1. both are singletons and 2. the storage
 *       session gets its config from the security session...
 */
class AntiCSRF
{
    protected string $storageKey = 'csrfTokens';
    protected int $maxTokens = 65535;

    /**
     * @param ?string $lockTo passing in a null will create a token matching all forms
     * @throws \Exception in case not enough randomness can be mustered
     * @throws \RuntimeException
     */
    public function getToken(?string $lockTo = null): string
    {
        $session = Session::getInstance();
        $tokens = $session->get($this->storageKey, []);
        list($tokenIndex, $tokenData) = $this->generateToken($lockTo);
        $tokens[$tokenIndex] = $tokenData;
        $tokens = $this->pruneTokens($tokens);
        $session->set($this->storageKey, $tokens);
        return "{$tokenIndex}:{$tokenData['token']}";
    }

    /**
     * @param ?string $lockTo
     * @return array 1st element: token index (string), 2nd: token data (array)
     * @throws \Exception in case not enough randomness can be mustered
     * @todo allow to optionally lock down to the current client's IP
     */
    protected function generateToken(?string $lockTo): array
    {
        $index = base64_encode(random_bytes(18));
        $token = base64_encode(random_bytes(33));

        return [$index, [
            'created' => time(),
            'token' => $token,
            'lockTo' => $lockTo,
            'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'],
        ]];
    }

    /**
     * Enforce an upper limit on the number of tokens stored by removing the oldest tokens first.
     */
    protected function pruneTokens($tokensArray): array
    {
        if (count($tokensArray) <= $this->maxTokens) {
            return $tokensArray;
        }
        // sort newest first (bigger creation time)
        uasort($tokensArray, function (array $a, array $b): int {
            return -1 * ($a['created'] <=> $b['created']);
        });
        $tokensArray = array_slice($tokensArray, 0, $this->maxTokens, true);
        return $tokensArray;
    }

    /**
     * @throws AntiCSRFException
     * @throws \RuntimeException
     */
    public function validateToken(string $token, ?string $lockTo = ''): void
    {
        if ($token === '') {
            throw new TokenIsMissingException('Anti-CSRF token not found');
        }
        $parts = explode(':', $token);
        if (count($parts) !== 2) {
            throw new TokenHasInvalidFormatException('Anti-CSRF token has an unsupported format');
        }
        $tokenIndex = $parts[0];
        $tokenHash = $parts[1];

        $session = Session::getInstance();
        $tokens = $session->get($this->storageKey, []);
        if (!array_key_exists($tokenIndex, $tokens)) {
            throw new TokenIndexNotInSessionException('Anti-CSRF token index not found in the session');
        }
        $token = $tokens[$tokenIndex];

        // remove the token from the session
        unset($tokens[$tokenIndex]);
        $session->set($this->storageKey, $tokens);

        // note: we do not check that the token creation date make is recent because we store them in the session,
        // so their lifetime is naturally limited (based on the session configuration)

        if (!hash_equals($tokenHash, $token['token'])) {
            throw new TokenHashMismatchException('Anti-CSRF did not match the stored value');
        }
        if ($lockTo !== null && $token['lockTo'] !== null && !hash_equals($lockTo, $token['lockTo'])) {
            throw new TokenMatchesWrongFormException('Anti-CSRF token used on the wrong form');
        }
    }
}
