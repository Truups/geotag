<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GooglePlaceService
{
    public function geocodeAddress(string $query): ?array
    {
        $apiKey = config('services.google_places.key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('Google Places API Keys Belum dikonfigurasi');
        }

        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->withHeaders([
                    'Content-Type: application/json',
                    'X-Goog-Api-Key: '.$apiKey,
                    'X-Goog-FieldMask: places.location,places.formattedAddress',
                    'Referer: bonfood.dev.askarasoft.com',
                ])
                ->post(
                    'https://places.googleapis.com/v1/places:searchText',
                    [
                        'textQuery' => $query,
                    ]
                );
        } catch (ConnectionException $err) {
            Log::warning('Google Places connection failed.', [
                'query' => $query,
                'message' => $err->getMessage(),
            ]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Google Places API Returned an error.', [
                'status' => $response->status(),
                'message' => data_get(
                    $response->json(),
                    'error.message'
                ),
            ]);

            return null;
        }

        $place = data_get($response->json(), 'places.0');
        if (! is_array($place) || ! isset($place['location']['latitude'], $place['location']['longitude'])) {
            return null;
        }

        return [
            'lat' => $place['location']['latitude'],
            'lng' => $place['location']['longitude'],
            'address' => $place['formattedAddress'] ?? '',
        ];
    }
}
