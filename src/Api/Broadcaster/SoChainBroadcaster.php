<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Broadcaster;

use DogeSweeper\Api\TransactionBroadcasterInterface;

/**
 * Broadcaster SoChain pour les transactions Dogecoin
 *
 * @package DogeSweeper\Api\Broadcaster
 */
class SoChainBroadcaster implements TransactionBroadcasterInterface
{
    private const BASE_URL = 'https://sochain.com/api/v2';
    private string $network;

    public function __construct(string $network = 'DOGE')
    {
        if (!in_array($network, ['DOGE', 'DOGETEST'], true)) {
            throw new \InvalidArgumentException(
                "Invalid network. Must be 'DOGE' or 'DOGETEST'"
            );
        }
        $this->network = $network;
    }

    /**
     * Broadcast une transaction signée
     *
     * @param string $txHex Transaction en hexadécimal
     * @return string ID de transaction
     */
    public function broadcast(string $txHex): string
    {
        $payload = http_build_query([
            'tx_hex' => $txHex
        ]);

        $url = self::BASE_URL . "/send_tx/{$this->network}";

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'User-Agent: doge-sweeper',
                ],
                'content' => $payload,
                'timeout' => 10,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException(
                "SoChain broadcast failed"
            );
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if ($data['status'] !== 'success') {
            throw new \RuntimeException(
                "SoChain broadcast error: {$data['data']['message'] ?? 'Unknown error'}"
            );
        }

        return $data['data']['txid'] ?? '';
    }

    /**
     * Récupère le statut d'une transaction
     *
     * @param string $txid ID de transaction
     * @return array<string, mixed> Statut et infos
     */
    public function getTransactionStatus(string $txid): array
    {
        $url = self::BASE_URL . "/tx/{$this->network}/{$txid}";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException(
                "Failed to get transaction status for {$txid}"
            );
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if ($data['status'] !== 'success') {
            throw new \RuntimeException(
                "SoChain error: {$data['data']['message'] ?? 'Unknown error'}"
            );
        }

        return $data['data'];
    }

    public function getName(): string
    {
        return 'SoChain';
    }
}
