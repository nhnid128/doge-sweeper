<?php

declare(strict_types=1);

namespace DogeSweeper\Integration;

use DogeSweeper\Exception\InvalidWifException;

/**
 * Interface avec libdogecoin via PHP FFI
 *
 * @package DogeSweeper\Integration
 */
class LibDogecoinFFI
{
    private ?\FFI $ffi = null;
    private string $libPath;
    private bool $isLoaded = false;
    private bool $eccInitialized = false;

    public function __construct(string $libPath = '')
    {
        if (!extension_loaded('ffi')) {
            throw new \RuntimeException(
                'FFI extension is not loaded. Enable it in php.ini: extension=ffi'
            );
        }

        $this->libPath = $libPath ?: $this->findLibDogecoin();

        if (!file_exists($this->libPath)) {
            throw new \RuntimeException(
                "libdogecoin not found at {$this->libPath}. " .
                "Please install libdogecoin 0.1.5+ and update the path."
            );
        }

        $this->loadFFI();
        $this->initializeECC();
    }

    private function loadFFI(): void
    {
        $cdef = <<<'C'
            typedef struct {
                unsigned char data[20];
            } dogecoin_hash160;

            typedef struct {
                unsigned char data[32];
            } dogecoin_pubkey;

            void dogecoin_ecc_start();
            void dogecoin_ecc_stop();
            int dogecoin_wif_to_privkey(const char *wif, unsigned char *privkey, int privkey_len);
            int dogecoin_pubkey_from_privkey(
                const unsigned char *privkey,
                unsigned char *pubkey,
                int *pubkey_len
            );
            int dogecoin_pubkey_to_pubkeyhash(
                const unsigned char *pubkey,
                int pubkey_len,
                unsigned char *hash160
            );
            int dogecoin_script_pubkey_from_hash160(
                const unsigned char *hash160,
                unsigned char version,
                unsigned char *script,
                int *script_len
            );
            int dogecoin_address_from_pubkeyhash(
                unsigned char version,
                const unsigned char *hash160,
                char *address,
                int address_len
            );
            int dogecoin_is_wif_valid(const char *wif);
        C;

        try {
            $this->ffi = \FFI::cdef($cdef, $this->libPath);
            $this->isLoaded = true;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Failed to load libdogecoin FFI: " . $e->getMessage()
            );
        }
    }

    private function initializeECC(): void
    {
        if (!$this->isLoaded) {
            throw new \RuntimeException('FFI not loaded');
        }

        try {
            $this->ffi->dogecoin_ecc_start();
            $this->eccInitialized = true;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Failed to initialize libdogecoin ECC context: " . $e->getMessage()
            );
        }
    }

    private function findLibDogecoin(): string
    {
        $possiblePaths = [
            '/usr/local/lib/libdogecoin.so',
            '/usr/lib/libdogecoin.so',
            '/usr/local/lib/libdogecoin.dylib',
            '/usr/lib/libdogecoin.dylib',
            'libdogecoin.so',
            'libdogecoin.dll',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return '/usr/local/lib/libdogecoin.so';
    }

    public function isValidWif(string $wif): bool
    {
        if (!$this->isLoaded || !$this->eccInitialized) {
            throw new \RuntimeException('FFI not properly initialized');
        }

        return (bool) $this->ffi->dogecoin_is_wif_valid($wif);
    }

    public function wifToPrivkey(string $wif): string
    {
        if (!$this->isLoaded || !$this->eccInitialized) {
            throw new \RuntimeException('FFI not properly initialized');
        }

        if (!$this->isValidWif($wif)) {
            throw new InvalidWifException($wif, 'Invalid WIF format');
        }

        $privkey = \FFI::new('unsigned char[32]');

        try {
            $result = $this->ffi->dogecoin_wif_to_privkey($wif, $privkey, 32);

            if (!$result) {
                throw new InvalidWifException($wif, 'Conversion failed');
            }

            $hex = '';
            for ($i = 0; $i < 32; $i++) {
                $hex .= sprintf('%02x', $privkey[$i]);
            }

            return $hex;
        } catch (\Throwable $e) {
            throw new InvalidWifException($wif, $e->getMessage());
        }
    }

    public function addressFromWif(string $wif, int $version = 0x1e): string
    {
        if (!$this->isLoaded || !$this->eccInitialized) {
            throw new \RuntimeException('FFI not properly initialized');
        }

        if (!$this->isValidWif($wif)) {
            throw new InvalidWifException($wif, 'Invalid WIF format');
        }

        try {
            $privkey = \FFI::new('unsigned char[32]');
            if (!$this->ffi->dogecoin_wif_to_privkey($wif, $privkey, 32)) {
                throw new InvalidWifException($wif, 'Cannot decode WIF');
            }

            $pubkey = \FFI::new('unsigned char[65]');
            $pubkeyLen = \FFI::new('int');
            $pubkeyLen->cdata = 65;

            if (!$this->ffi->dogecoin_pubkey_from_privkey($privkey, $pubkey, \FFI::addr($pubkeyLen))) {
                throw new InvalidWifException($wif, 'Cannot generate public key');
            }

            $hash160 = \FFI::new('unsigned char[20]');
            if (!$this->ffi->dogecoin_pubkey_to_pubkeyhash($pubkey, $pubkeyLen->cdata, $hash160)) {
                throw new InvalidWifException($wif, 'Cannot hash public key');
            }

            $address = \FFI::new('char[100]');
            if (!$this->ffi->dogecoin_address_from_pubkeyhash($version, $hash160, $address, 100)) {
                throw new InvalidWifException($wif, 'Cannot generate address');
            }

            return \FFI::string($address);
        } catch (\Throwable $e) {
            if ($e instanceof InvalidWifException) {
                throw $e;
            }
            throw new InvalidWifException($wif, $e->getMessage());
        }
    }

    public function getLibPath(): string
    {
        return $this->libPath;
    }

    public function isLoaded(): bool
    {
        return $this->isLoaded && $this->eccInitialized;
    }

    public function __destruct()
    {
        if ($this->ffi && $this->eccInitialized) {
            try {
                $this->ffi->dogecoin_ecc_stop();
            } catch (\Throwable $e) {
                // Silently ignore cleanup errors
            }
        }
    }
}
