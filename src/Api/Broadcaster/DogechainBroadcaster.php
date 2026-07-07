<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Broadcaster;

use DogeSweeper\Api\TransactionBroadcasterInterface;

/**
 * Broadcaster dogechain.info pour les transactions Dogecoin
 *
 * @package DogeSweeper\Api\Broadcaster
 */
class DogechainBroadcaster implements TransactionBroadcasterInterface
{
    private const BASE_URL = 'https://dogechain.info/api/v1';

    /**
     * Broadcast une transaction signée
     *
     * @param string $txHex Transaction en hexadécimal
     * @return string ID de transaction
     */
    public function broadcast(string $txHex): string
    {
        $url = self::BASE_URL . '/tx/send';

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'User-Agent: doge-sweeper',
                ],
                'content' => http_build_query(['rawtx' => $txHex]),
                'timeout' => 10,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException(
                "dogechain.info broadcast failed"
            );
        }

        // dogechain.info retourne le TXID directement ou une erreur
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['error'])) {
            throw new \RuntimeException(
                "dogechain.info broadcast error: {$data['error']}"
            );
        }

        return $data['txid'] ?? $response;
    }

    /**
     * Récupère le statut d'une transaction
     *
     * @param string $txid ID de transaction
     * @return array<string, mixed> Statut et infos
     */
    public function getTransactionStatus(string $txid): array
    {
        $url = self::BASE_URL . "/tx/{$txid}";

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

        if (isset($data['error'])) {
            throw new \RuntimeException(
                "dogechain.info error: {$data['error']}"
            );
        }

        return $data;
    }

    public function getName(): string
    {
        return 'dogechain.info';
    }
}
