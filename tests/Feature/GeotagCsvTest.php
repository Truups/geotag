<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

it('mengisi latitude dan longitude dari Google Places', function () {
    config()->set('services.google_places.key', 'test-key');

    Http::fake([
        'places.googleapis.com/*' => Http::response([
            'places' => [
                [
                    'location' => ['latitude' => 6.123, 'longitude' => 102.456],
                    'formattedAddress' => 'Kota Bharu, Kelantan',
                ],
            ],
        ]),
    ]);

    $csv = "Company Name,Address\nKedai Kopi Ali,Jalan Besar\n";
    $file = UploadedFile::fake()->createWithContent('outlet.csv', $csv);

    $response = $this->post(route('geotag.process'), ['csv' => $file]);

    $response->assertOk();
    $response->assertDownload('outlet-geotag.csv');

    $path = $response->baseResponse->getFile()->getPathname();
    $rows = array_map('str_getcsv', file($path));

    expect($rows[0])->toBe(['Company Name', 'Address', 'latitude', 'longitude']);
    expect($rows[1][2])->toBe('6.123');
    expect($rows[1][3])->toBe('102.456');
});

it('mengosongkan koordinat ketika tempat tidak ditemukan', function () {
    config()->set('services.google_places.key', 'test-key');

    Http::fake([
        'places.googleapis.com/*' => Http::response(['places' => []]),
    ]);

    $csv = "Company Name\nToko Tidak Ada\n";
    $file = UploadedFile::fake()->createWithContent('outlet.csv', $csv);

    $response = $this->post(route('geotag.process'), ['csv' => $file]);

    $response->assertOk();
    $response->assertDownload('outlet-geotag.csv');

    $path = $response->baseResponse->getFile()->getPathname();
    $rows = array_map('str_getcsv', file($path));

    expect($rows[1][1])->toBe('');
    expect($rows[1][2])->toBe('');
});

it('gagal jika kolom Company Name tidak ada', function () {
    config()->set('services.google_places.key', 'test-key');

    $csv = "Nama,Alamat\nKedai Kopi Ali,Jalan Besar\n";
    $file = UploadedFile::fake()->createWithContent('outlet.csv', $csv);

    $this->post(route('geotag.process'), ['csv' => $file])
        ->assertSessionHasErrors('csv');
});
