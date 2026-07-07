<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Broadcaster;

use DogeSweeper\Api\TransactionBroadcasterInterface;

/**
 * Broadcaster BlockCypher pour les transactions Dogecoin
 *
 * @package DogeSweeper\Api\Broadcaster
 */
class BlockCypherBroadcaster implements TransactionBroadcasterInterface
{
    private const BASE_URL = 'https://api.blockcypher.com/v1/doge/main';
    private ?string $token;

    public function __construct(?string $token = null)
    {
        $this->token = $token;
    }

    /**
     * Broadcast une transaction signée
     *
     * @param string $txHex Transaction en hexadécimal
     * @return string ID de transaction
     */
    public function broadcast(string $txHex): string
    {
        $payload = json_encode(['tx' => $txHex]);

        $url = self::BASE_URL . '/txs/push';
        if ($this->token) {
            $url .= '?token=' . urlencode($this->token);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'User-Agent: doge-sweeper',
                ],
                'content' => $payload,
                'timeout' => 10,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException(
                "BlockCypher broadcast failed"
            );
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['error'])) {
            throw new \RuntimeException(
                "BlockCypher broadcast error: {$data['error']}"
            );
        }

        return $data['hash'] ?? $data['tx']['hash'] ?? '';
    }

    /**
     * Récupère le statut d'une transaction
     *
     * @param string $txid ID de transaction
     * @return array<string, mixed> Statut et infos
     */
    public function getTransactionStatus(string $txid): array
    {
        $url = self::BASE_URL . "/txs/{$txid}";
        if ($this->token) {
            $url .= '?token=' . urlencode($this->token);
        }

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

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getName(): string
    {
        return 'BlockCypher';
    }
}
