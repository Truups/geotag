@extends('layouts.app')

@section('title', 'Geotag Maker')
@section('content')
    <!-- Konten utama -->
    <main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">

        <!-- Judul halaman -->
        <header class="flex items-center gap-4 mb-8">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Geotag Maker</h1>
                <p class="mt-1 text-sm sm:text-base text-slate-500">Ekspor data geotag dari file yang dipilih dan unduh dalam format CSV.</p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Kartu form -->
            <section class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                <form id="exportForm" action="{{ route('geotag.process') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Pilih File -->
                    <div class="mt-10 flex items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-lg font-semibold text-slate-900">Pilih File</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Unggah file yang berisi data geotag. Format yang didukung: .csv</p>
                        </div>
                    </div>

                    <!-- Area drag & drop -->
                    <div id="dropzone"
                        class="mt-4 flex flex-col items-center rounded-xl border-2 border-dashed border-blue-300 bg-blue-50/40 px-4 py-10 text-center transition-colors">
                        <svg class="h-14 w-14 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 14.9A7 7 0 1 1 15.7 8h1.8a4.5 4.5 0 0 1 2.5 8.2" />
                            <path d="M12 12v9" />
                            <path d="m16 16-4-4-4 4" />
                        </svg>
                        <p class="mt-3 text-sm font-semibold text-slate-800">Drag &amp; drop file di sini</p>
                        <p class="mt-1 text-sm text-slate-500">atau</p>

                        <input id="fileInput" name="csv" type="file" accept=".csv" class="sr-only" />
                        <button id="browseBtn" type="button"
                            class="mt-3 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />
                            </svg>
                            Pilih File
                        </button>

                        <p class="mt-4 text-xs text-slate-500">Format: .csv &nbsp;|&nbsp; Maks. ukuran: 20 MB</p>
                    </div>

                    <!-- Pesan error -->
                    <p id="errorMsg" role="alert"
                        class="mt-3 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700"></p>

                    @error('csv')
                        <p role="alert" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">
                            {{ $message }}
                        </p>
                    @enderror

                    <!-- File terpilih -->
                    <div id="fileCard"
                        class="mt-4 hidden items-center gap-3 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded bg-emerald-600 text-xs font-bold text-white">X</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p id="fileName" class="truncate text-sm font-medium text-slate-800"></p>
                            <p class="text-xs text-slate-500"><span id="fileSize"></span> &bull; File siap diproses</p>
                        </div>
                        <button id="removeBtn" type="button" aria-label="Hapus file"
                            class="rounded-md p-1.5 text-slate-500 transition hover:bg-emerald-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" aria-hidden="true">
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Tombol export -->
                    <button id="exportBtn" type="submit" disabled
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 disabled:shadow-none">
                        <svg id="exportIcon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" y1="3" x2="12" y2="15" />
                        </svg>
                        <svg id="spinner" class="hidden h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                class="opacity-25" />
                            <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"
                                class="opacity-75" />
                        </svg>
                        <span id="exportLabel">Export ke CSV</span>
                    </button>
                </form>
            </section>

            <!-- Panel Informasi -->
            <aside class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Informasi</h2>
                <ul class="mt-5 space-y-5">
                    <li class="flex gap-3">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" />
                            <path d="M14 3v5h5" />
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Format File</p>
                            <p class="mt-0.5 text-sm leading-relaxed text-slate-500">Pastikan file yang diunggah sesuai dengan format yang didukung (.csv).</p>
                        </div>
                    </li>

                    <li class="flex gap-3">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M12 3v12" />
                            <path d="m7 11 5 5 5-5" />
                            <path d="M5 21h14" />
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Hasil Export</p>
                            <p class="mt-0.5 text-sm leading-relaxed text-slate-500">Setelah proses selesai, file CSV akan
                                langsung diunduh ke perangkat Anda.</p>
                        </div>
                    </li>
                </ul>

                <div class="mt-6 rounded-lg bg-blue-50 p-4">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">i</span>
                        <p class="text-sm font-semibold text-slate-900">Contoh Format CSV</p>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-slate-500">File CSV yang dihasilkan akan berisi informasi
                        seperti:</p>
                    <ul class="mt-2 list-disc space-y-1.5 pl-5 text-xs text-slate-600 marker:text-slate-400">
                        <li>Nama Outlet</li>
                        <li>Latitude</li>
                        <li>Longitude</li>
                    </ul>
                </div>
            </aside>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('exportForm');
            const dropzone = document.getElementById('dropzone');
            const fileInput = document.getElementById('fileInput');
            const browseBtn = document.getElementById('browseBtn');
            const fileCard = document.getElementById('fileCard');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const removeBtn = document.getElementById('removeBtn');
            const errorMsg = document.getElementById('errorMsg');
            const exportBtn = document.getElementById('exportBtn');
            const exportIcon = document.getElementById('exportIcon');
            const spinner = document.getElementById('spinner');
            const exportLabel = document.getElementById('exportLabel');

            const showError = (message) => {
                errorMsg.textContent = message;
                errorMsg.classList.remove('hidden');
            };

            const clearError = () => {
                errorMsg.textContent = '';
                errorMsg.classList.add('hidden');
            };

            const formatBytes = (bytes) => {
                if (bytes < 1024) {
                    return `${bytes} B`;
                }

                if (bytes < 1024 * 1024) {
                    return `${(bytes / 1024).toFixed(1)} KB`;
                }

                return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
            };

            const setLoading = (loading) => {
                exportBtn.disabled = loading;
                exportIcon.classList.toggle('hidden', loading);
                spinner.classList.toggle('hidden', !loading);
                exportLabel.textContent = loading ? 'Memproses...' : 'Export ke CSV';
            };

            const updateFileCard = () => {
                const file = fileInput.files[0];

                if (!file) {
                    fileCard.classList.add('hidden');
                    fileCard.classList.remove('flex');
                    exportBtn.disabled = true;

                    return;
                }

                fileName.textContent = file.name;
                fileSize.textContent = formatBytes(file.size);
                fileCard.classList.remove('hidden');
                fileCard.classList.add('flex');
                exportBtn.disabled = false;
            };

            browseBtn.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', () => {
                clearError();
                updateFileCard();
            });

            removeBtn.addEventListener('click', () => {
                fileInput.value = '';
                clearError();
                updateFileCard();
            });

            ['dragenter', 'dragover'].forEach((event) => {
                dropzone.addEventListener(event, (e) => {
                    e.preventDefault();
                    dropzone.classList.add('border-blue-500', 'bg-blue-50');
                });
            });

            ['dragleave', 'drop'].forEach((event) => {
                dropzone.addEventListener(event, (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('border-blue-500', 'bg-blue-50');
                });
            });

            dropzone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;

                if (files.length) {
                    fileInput.files = files;
                    clearError();
                    updateFileCard();
                }
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearError();

                if (!fileInput.files.length) {
                    showError('Silakan pilih file CSV terlebih dahulu.');

                    return;
                }

                setLoading(true);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });

                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));

                        throw new Error(
                            data.message || data.errors?.csv?.[0] || 'Gagal memproses file.'
                        );
                    }

                    const blob = await response.blob();
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'outlet-geotag.csv';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    URL.revokeObjectURL(url);
                } catch (error) {
                    showError(error.message);
                } finally {
                    setLoading(false);
                }
            });
        })();
    </script>
@endpush
