<?php
namespace App\Models\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;
use Illuminate\Contracts\Container\BindingResolutionException;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Illuminate\Support\Facades\Log;

class Zendesk
{
    public static function postFile($fileName, $fileData, $mimeType) {
        $base_uri = config('app.zendesk.url') . '/api/v2/';
        $client = new Client([
            'base_uri' => $base_uri,
        ]);

        $config = [
            'auth' => [
                config('app.zendesk.username') . '/token',
                config('app.zendesk.token')
            ],
            'headers' => [
                'Content-type' => $mimeType
            ],
            'body' =>  $fileData
        ];

        $response = $client->post("uploads.json?filename=" . urlencode($fileName), $config);

        $data = json_decode($response->getBody()->getContents(), true);
        if(isset($data["upload"])) {
            if(isset($data["upload"]["token"])) {
                return $data["upload"]["token"];
            }
        }
        return "";
    }

    /**
     * Search for tickets
     * @param string $query
     * @return mixed
     * @throws BindingResolutionException
     * @throws GuzzleException
     * @throws RuntimeException
     */
    public static function search($query) {
        return self::requestZendesk('search', 'GET', [
            'sort_by' => 'created_at',
            'sort_order' => 'desc',
            'query' => $query,
        ]);
    }

    /**
     * Create a ticket
     * Ticket array format: https://developer.zendesk.com/api-reference/ticketing/tickets/tickets/#json-format
     * @param array $ticket array of ticket info
     * @return mixed
     * @throws BindingResolutionException
     * @throws GuzzleException
     * @throws RuntimeException
     */
    public static function createTicket($ticket)
    {
        if (config('app.zendesk.log_mode', false)) {
            Log::info('Zendesk ticket would be created with data:', ['ticket' => $ticket]);
            // Return a more realistic response shape similar to what Zendesk API would return
            $fakeId = rand(100000, 999999);
            return [
                'ticket' => array_merge($ticket, [
                    'id' => $fakeId,
                ])
            ];
        }

        return self::requestZendesk('tickets.json', 'POST', ['async' => true], ['ticket' => $ticket]);
    }

    /**
     * Update a ticket
     * Ticket array format: https://developer.zendesk.com/api-reference/ticketing/tickets/tickets/#json-format
     * @param int $ticketId The ID of the ticket to update
     * @param array $ticket array of ticket update info
     * @return mixed
     * @throws BindingResolutionException
     * @throws GuzzleException
     * @throws RuntimeException
     */
    public static function updateTicket($ticketId, $ticket)
    {
        if (config('app.zendesk.log_mode', false)) {
            Log::info('Zendesk ticket would be updated with data:', ['ticket_id' => $ticketId, 'ticket' => $ticket]);
            // Return a more realistic response shape similar to what Zendesk API would return
            return [
                'ticket' => array_merge($ticket, [
                    'id' => $ticketId,
                ])
            ];
        }

        return self::requestZendesk("tickets/{$ticketId}.json", 'PUT', null, ['ticket' => $ticket]);
    }

    private static function requestZendesk($path, $method, $query = null, $data = null)
    {
        $base_uri = config('app.zendesk.url') . '/api/v2/';
        $client = new Client([
            'base_uri' => $base_uri,
        ]);

        $config = [
            'method' => $method,
            'auth' => [
                config('app.zendesk.username') . '/token',
                config('app.zendesk.token')
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
        if ($data) {
            $config['json'] = $data;
        }
        if ($query) {
            $config['query'] = $query;
        }
        $response = $client->request($method, $path, $config);
        $data = json_decode($response->getBody()->getContents(), true);
        return $data;
    }

}
