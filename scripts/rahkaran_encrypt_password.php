<?php

// Usage: php scripts/rahkaran_encrypt_password.php <sessionId> <M-hex> <E-hex> <password>
// Prints the uppercase-hex encrypted password to use as the "password" field
// in the /Services/Framework/AuthenticationService.svc/login request body.

require __DIR__ . '/../vendor/autoload.php';

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PublicKey;
use phpseclib3\Math\BigInteger;

[$script, $sessionId, $modulusHex, $exponentHex, $password] = $argv;

$modulus = new BigInteger($modulusHex, 16);
$exponent = new BigInteger($exponentHex, 16);

$key = PublicKeyLoader::load([
    'n' => $modulus,
    'e' => $exponent,
]);

if (! $key instanceof PublicKey) {
    fwrite(STDERR, "Loaded key is not an RSA public key.\n");
    exit(1);
}

$publicKey = $key->withPadding(RSA::ENCRYPTION_PKCS1);

$plainText = $sessionId . '**' . $password;

echo strtoupper(bin2hex($publicKey->encrypt($plainText))) . "\n";
