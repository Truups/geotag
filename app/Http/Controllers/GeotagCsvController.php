<?php

namespace App\Http\Controllers;

use App\Services\GooglePlaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GeotagCsvController extends Controller
{
    public function create()
    {
        return view('geotag.export');
    }

    public function process(Request $request,GooglePlaceService $googlePlaces) {
        set_time_limit(0);

        // Validasi file CSV.
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        $inputPath = $request->file('csv')->getRealPath();
        $input = fopen($inputPath, 'r');

        if ($input === false) {
            throw ValidationException::withMessages(['csv' => 'File CSV tidak dapat dibaca.']);
        }

        // Membaca header CSV.
        $header = fgetcsv($input, 0, ',', '"', '');

        if ($header === false) {
            fclose($input);
            throw ValidationException::withMessages(['csv' => 'File CSV Kosong atau tidak valid.']);
        }

        // Menghapus UTF-8 BOM jika ada.
        if (isset($header[0])) {
            $header[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $header[0]
            );
        }

        $header = array_map(
            fn ($column) => trim((string) $column),
            $header
        );

        // Memastikan kolom yang dibutuhkan tersedia.
        if (!in_array('Company Name', $header, true)) {
            fclose($input);
            throw ValidationException::withMessages(['csv' => 'Kolom "Company Name" tidak ditemukan.']);
        }

        // Header asli dipakai saat membaca setiap baris.
        $inputHeader = $header;

        // Menambahkan kolom jika belum ada.
        $outputHeader = $header;

        foreach (['latitude', 'longitude'] as $column) {
            if (!in_array($column, $outputHeader, true)) {
                $outputHeader[] = $column;
            }
        }

        // Menyiapkan direktori penyimpanan hasil sementara.
        $directory = storage_path('app/exports');

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            fclose($input);
            throw ValidationException::withMessages(['csv' => 'Direktori Eskpor tidak dapat dibuat.']);
        }

        $outputPath = $directory . '/outlet-geotag-' . now()->format('YmdHis') . '-' . Str::random(6) . '.csv';
        $output = fopen($outputPath, 'w');

        if ($output === false) {
            fclose($input);
            throw ValidationException::withMessages(['csv' => 'File Hasil CSV tidak dapat dibuat.']);
        }

        // Menulis header hasil.
        fputcsv($output, $outputHeader, ',', '"', '');

        // Memproses setiap outlet.
        while (($row = fgetcsv($input, 0, ',', '"', '')) !== false) {
            // Lewati baris yang jumlah kolomnya tidak sesuai.
            if (count($row) !== count($inputHeader)) {
                continue;
            }

            $record = array_combine($inputHeader, $row);
            $companyName = trim(
                (string) ($record['Company Name'] ?? '')
            );

            $geo = null;

            if ($companyName !== '') {
                // Cari koordinat menggunakan Google Places.
                $geo = $googlePlaces->geocodeAddress($companyName);
                usleep(200000);
            }

            // Isi koordinat atau string kosong jika gagal.
            $record['latitude'] = $geo['lat'] ?? '';
            $record['longitude'] = $geo['lng'] ?? '';

            // Urutan kolom mengikuti header output.
            $outputRow = [];

            foreach ($outputHeader as $column) {
                $outputRow[] = $record[$column] ?? '';
            }

            fputcsv($output, $outputRow, ',', '"', '');
        }

        fclose($input);
        fclose($output);
        return response()
            ->download(
                $outputPath,
                'outlet-geotag.csv',
                ['Content-Type' => 'text/csv; charset=UTF-8']
            )
            ->deleteFileAfterSend(true);
    }
}