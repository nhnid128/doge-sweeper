<?php

declare(strict_types=1);

namespace DogeSweeper\Config;

/**
 * Gestionnaire de configuration centralisé
 *
 * @package DogeSweeper\Config
 */
class Config
{
    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * Initialise la configuration
     *
     * @param array<string, mixed> $customConfig Configuration personnalisée
     */
    public function __construct(array $customConfig = [])
    {
        // Charger la configuration par défaut
        $defaultConfig = require __DIR__ . '/../../config/default.php';
        $this->config = array_merge($defaultConfig, $customConfig);

        // Charger la configuration locale si elle existe
        $localConfigPath = __DIR__ . '/../../config/local.php';
        if (file_exists($localConfigPath)) {
            $localConfig = require $localConfigPath;
            $this->config = array_merge($this->config, $localConfig);
        }
    }

    /**
     * Obtient une valeur de configuration
     *
     * @param string $key Clé de configuration
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Définit une valeur de configuration
     *
     * @param string $key Clé de configuration
     * @param mixed $value Valeur
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Vérifie qu'une clé existe
     *
     * @param string $key Clé de configuration
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * Obtient toute la configuration
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Valide la configuration requise
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate(): bool
    {
        if (empty($this->config['binance_address'])) {
            throw new \InvalidArgumentException('binance_address must be configured');
        }

        if ($this->config['fee_rate'] <= 0) {
            throw new \InvalidArgumentException('fee_rate must be positive');
        }

        if (!in_array($this->config['network'], ['mainnet', 'testnet'], true)) {
            throw new \InvalidArgumentException('network must be mainnet or testnet');
        }

        return true;
    }
}
