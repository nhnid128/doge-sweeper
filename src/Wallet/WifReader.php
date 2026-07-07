<?php

declare(strict_types=1);

namespace DogeSweeper\Wallet;

/**
 * Lecteur de fichier WIF
 *
 * @package DogeSweeper\Wallet
 */
class WifReader
{
    private string $filePath;
    private array $wifs = [];
    private array $invalidWifs = [];

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function read(): array
    {
        if (!file_exists($this->filePath)) {
            throw new \RuntimeException("WIF file not found: {$this->filePath}");
        }

        if (!is_readable($this->filePath)) {
            throw new \RuntimeException("WIF file not readable: {$this->filePath}");
        }

        $this->wifs = [];
        $this->invalidWifs = [];

        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open WIF file: {$this->filePath}");
        }

        try {
            $lineNumber = 0;
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                $wif = trim($line);

                if (empty($wif) || str_starts_with($wif, '#')) {
                    continue;
                }

                if ($this->isValidWifFormat($wif)) {
                    $this->wifs[] = $wif;
                } else {
                    $this->invalidWifs[] = [
                        'line' => $lineNumber,
                        'wif' => $wif,
                        'reason' => 'Invalid format',
                    ];
                }
            }
        } finally {
            fclose($handle);
        }

        return $this->wifs;
    }

    private function isValidWifFormat(string $wif): bool
    {
        $length = strlen($wif);

        if ($length < 50 || $length > 56) {
            return false;
        }

        $firstChar = $wif[0];
        if (!in_array($firstChar, ['Q', 'K', 'c'], true)) {
            return false;
        }

        if (!preg_match('/^[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]+$/', $wif)) {
            return false;
        }

        return true;
    }

    public function getValidWifs(): array
    {
        return $this->wifs;
    }

    public function getInvalidWifs(): array
    {
        return $this->invalidWifs;
    }

    public function getValidCount(): int
    {
        return count($this->wifs);
    }

    public function getInvalidCount(): int
    {
        return count($this->invalidWifs);
    }

    public function getSummary(): string
    {
        $summary = "WIF Reader Summary:\n";
        $summary .= "  Valid WIFs: {$this->getValidCount()}\n";
        $summary .= "  Invalid WIFs: {$this->getInvalidCount()}\n";

        if (!empty($this->invalidWifs)) {
            $summary .= "\n  Invalid entries:\n";
            foreach ($this->invalidWifs as $invalid) {
                $summary .= "    Line {$invalid['line']}: {$invalid['wif']} ({$invalid['reason']})\n";
            }
        }

        return $summary;
    }
}
