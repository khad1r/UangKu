<?php

namespace App\libs;

use Mcp\Capability\Attribute\{McpTool, Schema};
use Mcp\Exception\ToolCallException;
use Mcp\Exception\ResourceReadException;
use Mcp\Schema\ToolAnnotations;
use App\models\Transaksi as ModelsTransaksi;

class transaksi
{
  /**
   * Mencatat transaksi keuangan baru (Pemasukan, Pengeluaran, atau Pindah Buku)
   */
  #[McpTool(
    name: 'catat_transaksi',
    description: 'Mencatat transaksi keuangan baru (Pemasukan, Pengeluaran, atau Pindah Buku) ke sistem UangKu.

    ⚠️ RESTRICTION & MANDATORY WORKFLOW (WAJIB DIBACA & DIIKUTI):
    1. WAJIB MEMAHAMI SYSTEM PROMPT: AI wajib selalu mengetahui dan mengikuti panduan lengkap dari resource system_prompt (uangku://system_prompt dari resources.lib.php).
    2. DILARANG EKSEKUSI LANGSUNG: DILARANG memanggil tool ini pada giliran pertama saat user baru memberikan rincian/struk belanja atau sebelum konfirmasi user.
    3. TAHAPAN WAJIB SEBELUM EKSEKUSI:
       a. Panggil "get_rekening" & "get_kelompok" secara fresh pada setiap turn (DILARANG pakai ID dari ingatan/turn sebelumnya).
       b. Susun data transaksi dan TAMPILKAN FORMAT REKAP ke user dalam raw CODE BLOCK (```).
       c. TUNGGU KONFIRMASI EKSPLISIT dari user (misal: "oke", "ya", "gas", "simpan", dsb.) atau koreksi dari user.
       d. Baru panggil tool "catat_transaksi" ini SETELAH user memberikan konfirmasi persetujuan.

    FORMAT REKAP (SEBELUM EXECUTION) YANG WAJIB DITAMPILKAN KE USER:
    Tampilkan rekap dalam raw CODE BLOCK (```, BUKAN rendered markdown table) dengan perataan vertikal karakter | yang rapi:
    ```
    | Barang          | Nominal | Qty | Rekening        | Kelompok    | Rutin | Tanggal    |
    | Kopi Susu       | 18.000  | 1   | ShopeePay (8)   | Konsumsi    | ✓     | 2026-08-23 |
    | Roti Cokelat    | 12.000  | 1   | ShopeePay (8)   | Konsumsi    | ✓     | 2026-08-23 |

    Auto relate: ✓
    Attachment: x
    ```
    Aturan Format Rekap:
    - Kolom: | Barang | Nominal | Qty | Rekening | Kelompok | Rutin | Tanggal |
    - Kolom Rekening WAJIB menyertakan nama rekening beserta ID dari get_rekening(), contoh: "ShopeePay (8)".
    - Kolom harus sejajar secara vertikal (semua karakter | lurus).
    - Jeda satu baris setelah tabel lalu cantumkan "Auto relate: x/✓" dan "Attachment: x/✓" (gunakan simbol ✓ untuk true dan x untuk false).
    - Tuliskan catatan asumsi singkat di bawah code block jika ada (misal: prorata diskon atau default account).

    ATURAN BISNIS PENTING:
    1. STRUK BELANJA: Jika input berupa struk dengan banyak item, JANGAN dicatat sebagai satu total. Pecah menjadi item individu dalam array "data". Beri parameter "autoRelate: true" agar item-item otomatis berelasi dengan item pertama.
    2. TRANSAKSI HARTA (ASET):
      - Pembelian = Pengeluaran (rekening uang, harta=false) + Pemasukan (rekening Harta, harta=true, isi penyusutan_bunga), relasikan keduanya via relasi_transaksi.
      - Penjualan/Pembuangan = Pengeluaran (rekening Harta, harta=true) + Pemasukan (rekening uang, bila ada hasil jual), relasikan keduanya.
      - DILARANG: harta=true pada Pengeluaran dari rekening uang biasa.
      - DILARANG: Pindah Buku untuk Harta.
    3. NOMINAL ASING: Gunakan "nominal_asing" jika transaksi melibatkan Emas (dalam Gram) atau mata uang asing seperti USD (Paypal).
    4. VALIDASI: WAJIB panggil tool "get_rekening" ULANG tepat sebelum menyusun rekap dan memanggil tool ini, SETIAP KALI.
    5. DISKON:
      - Jika diskon per item: Catat harga NETTO (setelah diskon).
      - Jika diskon total di akhir struk: Gunakan metode PRORATA (bagi diskon ke setiap item secara proporsional).
    6. KURS LOGIC: Perubahan nilai tukar dicatat sebagai Pemasukan/Pengeluaran pada kolom nominal (selisihnya), dengan nominal_asing = 0.',
    annotations: new ToolAnnotations(
      readOnlyHint: false,
      destructiveHint: false,
      idempotentHint: false,
      openWorldHint: false
    ),
    outputSchema: [
      'type' => 'object',
      'properties' => [
        'status' => ['type' => 'string', 'description' => 'Status pencatatan transaksi & link upload attachment buat user']
      ]
    ]
  )]
  #[Schema(
    properties: [
      'data' => [
        'type' => 'array',
        'description' => 'Daftar transaksi yang akan dicatat. Berupa array of objects berisi detail transaksi. WAJIB pastikan user sudah melihat rekap dan memberikan konfirmasi sebelum mengeksekusi tool ini.',
        'items' => [
          'type' => 'object',
          'properties' => [
            'jenis_transaksi'   => [
              'type' => 'string',
              'enum' => ['Pengeluaran', 'Pemasukan', 'Pindah Buku'],
              'description' => 'Tipe transaksi'
            ],
            'harta'             => ['type' => 'boolean', 'description' => '
                Set TRUE jika rekening yang digunakan adalah rekening Harta (baik Pemasukan maupun Pengeluaran).
                DILARANG set TRUE pada Pengeluaran dari rekening uang biasa.
              '],
            'barang'            => ['type' => 'string', 'description' => 'Nama barang atau deskripsi singkat, Harus di generalkan jangan terlalu spesifik (contoh: "Makan siang" bukan "Nasi Padang Sari Ratu"), Gunakan Keterangan untuk lainnya.'],
            'rekening_sumber'   => [
              'type' => ['integer', 'null'],
              'description' => 'ID Rekening asal. WAJIB ambil dari hasil pemanggilan get_rekening PALING BARU (turn ini) — JANGAN pernah pakai ID dari ingatan/percakapan sebelumnya. (Wajib jika Pengeluaran/Pindah Buku)
                  ATURAN PINDAH BUKU:
                  1. Tidak boleh sama dengan rekening_masuk.
                  2. Dilarang menggunakan rekening tipe HARTA (Aset).
                  3. Jika rekening asal adalah mata uang ASING (Emas/USD), maka rekening tujuan HARUS memiliki jenis mata uang asing yang sama.'
            ],
            'rekening_masuk'    => [
              'type' => ['integer', 'null'],
              'description' => 'ID Rekening tujuan. WAJIB ambil dari hasil pemanggilan get_rekening PALING BARU (turn ini) — JANGAN pernah pakai ID dari ingatan/percakapan sebelumnya. (Wajib jika Pemasukan/Pindah Buku)
                ATURAN PINDAH BUKU:
                1. Tidak boleh sama dengan rekening_sumber.
                2. Dilarang menggunakan rekening tipe HARTA (Aset).
                3. Jika rekening tujuan adalah mata uang ASING (Emas/USD), maka rekening sumber HARUS memiliki jenis mata uang asing yang sama.'
            ],
            'nominal'           => [
              'type' => 'number',
              'description' => 'Jumlah Per Kuantitas dalam Rupiah. Untuk selisih kurs: isi selisih nilainya di sini, set nominal_asing = 0.'
            ],
            'nominal_asing'     => [
              'type' => ['number', 'null'],
              'description' => 'Wajib diisi jika rekening menggunakan mata uang asing (Emas/USD). Masukkan nilai dalam satuan aslinya (misal: 0.1 untuk emas gram, bukan nilai rupiahnya).'
            ],
            'kuantitas'         => ['type' => 'number', 'default' => 1],
            'penyusutan_bunga'  => [
              'type' => 'number',
              'default' => 0,
              'description' => 'Nominal Penyusutan / Bunga buku per bulan dalam Rupiah. Hanya diisi jika jenis_transaksi adalah Pemasukan dan harta = true dan Jenis Rekening adalah Harta, untuk mencatat penyusutan atau bunga aset (misal: bunga deposito).'
            ],
            'rutin'             => [
              'type' => 'boolean',
              'default' => false,
              'description' => 'KLASIFIKASI RUTINITAS. Tentukan dengan urutan prioritas ini (hentikan di aturan pertama yang cocok — lihat system_prompt bagian RUTIN vs NON-RUTIN untuk detail & contoh):
                1. Bagian dari Event/Perjadin/Mudik -> FALSE, selalu.
                2. Tagihan bulanan tetap untuk kebutuhan hidup/kerja dasar (Kos, Listrik, Admin Rekening, paket data esensial) -> TRUE, walau dibayar hari Minggu.
                3. Langganan hiburan/non-esensial (Gojek Plus, Bilibili, Arknights, app non-esensial), pembelian aset/gadget/furnitur, atau GoFood/delivery -> FALSE.
                4. Transaksi hari Minggu -> FALSE.
                5. Kelompok harian (Konsumsi, Transportasi, Sedekah, Topup) di hari Senin-Sabtu -> TRUE.
                6. Masih ragu -> cek breakdown rutin/count per kelompok dari get_kelompok(); ikuti nilai rutin yang historisnya dominan untuk kelompok tsb.
                7. Masih ragu juga -> FALSE (default non-rutin).'
            ],
            'kelompok'          => [
              'type' => ['string', 'null'],
              'default' => null,
              'description' => 'Kategori transaksi. Gunakan get_kelompok untuk referensi, boleh buat baru sesuai kebutuhan. Aturan penentuan kelompok:
                1. OPERASIONAL (Rutin): Gunakan kategori umum (contoh: "Konsumsi", "Transportasi", "Listrik") jika dilakukan untuk kebutuhan dasar harian.
                2. LIFESTYLE (Non-Rutin Umum): Gunakan kategori umum jika terjadi di hari Minggu atau bersifat insidentil (bukan kebutuhan kerja harian).
                3. PROYEK/EVENT: Wajib buat/gunakan satu nama kelompok unik (contoh: "Liburan Bali 2026", "Perjadin 7 April") untuk SEMUA item (makan, tiket, dll) jika transaksi adalah bagian dari agenda khusus tersebut.
                DILARANG ASAL PILIH: Analisis konteks waktu dan tujuan transaksi sebelum menentukan kelompok.'
            ],
            'tanggal'           => [
              'type' => ['string', 'null'],
              'format' => 'date',
              'description' => 'Format: YYYY-MM-DD'
            ],
            'relasi_transaksi'  => [
              'type' => ['integer', 'null'],
              'description' =>
              'ID transaksi utama untuk menghubungkan beberapa item dalam satu struk/kejadian.,
                - ID ini bisa didapatkan dari response setelah mencatat transaksi melalui tool ini dengan format "✅ Berhasil! Transaksi Id #{id} telah dicatat.".
                - Jika menginput struk belanja, catat item pertama, dapatkan ID-nya, lalu gunakan ID tersebut di field ini untuk item-item selanjutnya.
        '
            ],
            'keterangan'        => ['type' => ['string', 'null'], 'default' => '', 'description' => 'Informasi tambahan tentang transaksi. Gunakan untuk detail spesifik yang tidak tercakup di field lain (contoh: nama toko, metode pembayaran, alasan pembelian).'],
            'attachment' => [
              'type' => 'boolean',
              'description' => 'Set TRUE jika transaksi memerlukan/mempunyai struk/nota/bukti transaksi (attachment) yang ingin diunggah oleh user.'
            ]
          ],
          'required' => ['jenis_transaksi', 'barang', 'nominal', 'kuantitas', 'tanggal']
        ]
      ],
      'autoRelate' => ['type' => 'boolean', 'default' => false]
    ],
    required: ['data']
  )]
  public function catatTransaksi(array $data, bool $autoRelate = false): string
  {
    $transaksi = new ModelsTransaksi();
    $results = [];
    $firstInsertId = null;

    try {
      foreach ($data as $index => $item) {
        // 1. Manual verification of required fields
        $requiredFields = ['jenis_transaksi', 'barang', 'nominal', 'kuantitas', 'tanggal'];
        foreach ($requiredFields as $field) {
          if (!array_key_exists($field, $item)) {
            throw new ToolCallException("❌ Validasi Gagal pada item ke-{$index}: Parameter '{$field}' wajib diisi.");
          }
        }

        // 2. Handle File Attachment (Will be uploaded via link later if requested)
        $finalFileName = !empty($item['attachment']) ? 'uploading' : null;

        // 3. Auto-relate items in a batch
        // If this is item > 0 in the array, and no relation is set, link it to the first item automatically
        $relasiTransaksi = $item['relasi_transaksi'] ?? null;
        if ($autoRelate && $index > 0 && $relasiTransaksi === null && $firstInsertId !== null) {
          $relasiTransaksi = $firstInsertId;
        }

        // 4. Map and normalize data payload
        $insertData = [
          'jenis_transaksi'   => $item['jenis_transaksi'],
          'harta'             => $item['harta'] ?? false,
          'barang'            => $item['barang'],
          'rekening_sumber'   => $item['rekening_sumber'] ?? null,
          'rekening_masuk'    => $item['rekening_masuk'] ?? null,
          'nominal'           => (float) $item['nominal'],
          'nominal_asing'     => isset($item['nominal_asing']) ? (float) $item['nominal_asing'] : 0,
          'kuantitas'         => (float) $item['kuantitas'],
          'penyusutan_bunga'  => isset($item['penyusutan_bunga']) ? (float) $item['penyusutan_bunga'] : 0,
          'rutin'             => $item['rutin'] ?? false,
          'kelompok'          => $item['kelompok'] ?? null,
          'tanggal'           => $item['tanggal'],
          'relasi_transaksi'  => $relasiTransaksi,
          'attachment'        => $finalFileName,
          'keterangan'        => $item['keterangan'] ?? null,
        ];

        // 5. Execute DB Insert
        $result = $transaksi->insertTransaksi($insertData);

        if ($result > 0) {
          $lastId = $transaksi->lastInsertId();

          // Capture the first ID to use for subsequent relations in this batch
          if ($index === 0) {
            $firstInsertId = $lastId;
          }

          $results[] = "#{$lastId} ({$item['barang']})";

          // If the user requested an attachment for this transaction, generate a link
          if (!empty($item['attachment'])) {
            $attachmentLink = "\n\n🔗 Harap unggah lampiran (attachment) untuk transaksi #{$lastId} di sini: " . BASEURL . "/Record/attachment/" . $lastId;
          }
        } else {
          throw new ToolCallException("❌ Gagal menyimpan item '{$item['barang']}' ke database.");
        }
      }

      $linkSuffix = isset($attachmentLink) ? $attachmentLink : "";
      return "✅ Berhasil! " . count($results) . " Transaksi dicatat: " . implode(";\n ", $results) . $linkSuffix;
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
  /**
   * Update transaksi Masal
   */
  #[McpTool(
    name: 'update_transaksi',
    description: 'Mendukung update massal hingga 15 transaksi sekaligus berdasarkan ID. Input berupa array of objects dengan format yang sama seperti catat_transaksi, namun wajib menyertakan field "id" untuk setiap item yang ingin diupdate. Contoh penggunaan:
    [
      {
        "id": 123,
        "jenis_transaksi": "Pengeluaran",
        "barang": "Makan Siang",
        "nominal": 50000,
        "kuantitas": 1,
        "tanggal": "2024-08-01",
        "keterangan": "Update: Tambah keterangan detail"
      },
      {
        "id": 124,
        "jenis_transaksi": "Pemasukan",
        "barang": "Gaji Bulanan",
        "nominal": 5000000,
        "kuantitas": 1,
        "tanggal": "2024-08-01",
        "keterangan": "Update: Ganti nominal sesuai slip gaji"
      }
    ]
    ATURAN PENTING:
      1. Batas Maksimal: Hanya mendukung update massal hingga 15 transaksi sekaligus untuk mencegah beban server yang berlebihan. Jika input melebihi batas ini, hanya 15 transaksi pertama yang akan diproses.
      2. Identifikasi Transaksi: Setiap objek dalam array input harus menyertakan field "id" yang valid untuk mengidentifikasi transaksi yang akan diupdate. Transaksi tanpa "id" atau dengan "id" yang tidak ditemukan di database akan diabaikan.
      3. Format Input: Format data untuk setiap transaksi yang akan diupdate sama seperti format yang digunakan dalam catat_transaksi, namun dengan tambahan field "id" yang wajib disertakan. Pastikan semua field yang diperlukan untuk update sudah benar dan sesuai dengan format yang ditentukan.
      ',
    annotations: new ToolAnnotations(
      readOnlyHint: false,
      destructiveHint: true,
      idempotentHint: true,
      openWorldHint: false
    ),
    outputSchema: [
      'type' => 'object',
      'properties' => [
        'status' => ['type' => 'string', 'description' => 'Status pencatatan transaksi & link upload attachment buat user']
      ]
    ]
  )]
  #[Schema(
    properties: [
      'data' => [
        'type' => 'array',
        'description' => 'Daftar transaksi yang akan diupdate. Berupa array of objects berisi detail transaksi.',
        'items' => [
          'type' => 'object',
          'properties' => [
            'id' => [
              'type' => 'integer',
              'description' => 'ID transaksi yang akan diupdate'
            ],
            'jenis_transaksi'   => [
              'type' => 'string',
              'enum' => ['Pengeluaran', 'Pemasukan', 'Pindah Buku'],
              'description' => 'Tipe transaksi'
            ],
            'harta'             => ['type' => 'boolean', 'description' => 'Set TRUE untuk aset permanen (HP, Motor, Emas). Set FALSE untuk habis pakai.'],
            'barang'            => ['type' => 'string', 'description' => 'Nama barang atau deskripsi singkat.'],
            'rekening_sumber'   => ['type' => ['integer', 'null'], 'description' => 'ID Rekening asal. WAJIB panggil get_rekening ulang dan pakai hasil terbaru bila mengubah field ini — jangan pakai ID dari ingatan.'],
            'rekening_masuk'    => ['type' => ['integer', 'null'], 'description' => 'ID Rekening tujuan. WAJIB panggil get_rekening ulang dan pakai hasil terbaru bila mengubah field ini — jangan pakai ID dari ingatan.'],
            'nominal'           => ['type' => 'number', 'description' => 'Jumlah dalam Rupiah.'],
            'nominal_asing'     => ['type' => ['number', 'null'], 'description' => 'Wajib diisi jika mata uang asing (Emas/USD).'],
            'kuantitas'         => ['type' => 'number', 'default' => 1],
            'penyusutan_bunga'  => ['type' => 'number', 'default' => 0],
            'rutin'             => ['type' => 'boolean', 'default' => false],
            'kelompok'          => ['type' => ['string', 'null'], 'default' => null],
            'tanggal'           => ['type' => ['string', 'null'], 'format' => 'date'],
            'relasi_transaksi'  => ['type' => ['integer', 'null']],
            'keterangan'        => ['type' => ['string', 'null'], 'default' => ''],
            'attachment'        => [
              'type' => 'boolean',
              'description' => 'Set TRUE jika ingin mengunggah/mengganti struk/nota/bukti transaksi (attachment) baru.'
            ]
          ],
          'required' => ['id', 'jenis_transaksi', 'harta', 'barang', 'rekening_sumber', 'rekening_masuk', 'nominal', 'nominal_asing', 'kuantitas', 'penyusutan_bunga', 'rutin', 'kelompok', 'tanggal', 'relasi_transaksi', 'keterangan']
        ]
      ]
    ],
    required: ['data']
  )]
  public function updateTransaksi(array $data): string
  {
    $transaksi = new ModelsTransaksi();
    $results = [];
    try {
      foreach ($data as $index => $item) {
        if ($index >= 15) break; // Batas maksimal 15 transaksi per update massal
        // 1. Manual verification of required fields
        $requiredFields = ['id', 'jenis_transaksi', 'harta', 'barang', 'rekening_sumber', 'rekening_masuk', 'nominal', 'nominal_asing', 'kuantitas', 'penyusutan_bunga', 'rutin', 'kelompok', 'tanggal', 'relasi_transaksi', 'keterangan'];

        foreach ($requiredFields as $field) {
          if (!array_key_exists($field, $item)) {
            throw new ToolCallException("❌ Validasi Gagal pada item ke-{$index}: Parameter '{$field}' wajib diisi.");
          }
        }
        $targetId = $item['id'] ?? null;

        if ($targetId) {
          $updateData = $item;
          unset($updateData['id']);

          // Check if user wants to upload an attachment for this updated transaction
          $wantsAttachment = !empty($updateData['attachment']);
          unset($updateData['attachment']);
          if ($wantsAttachment) {
            $updateData['attachment'] = 'uploading';
          }

          // This now happens in memory, making it lightning fast
          $transaksi->updateTransaksi($updateData, ['id' => $targetId]);
          $results[] = "#{$targetId} ({$item['barang']})";

          if ($wantsAttachment) {
            $attachmentLink = "\n\n🔗 Harap unggah lampiran (attachment) untuk transaksi #{$targetId} di sini: " . BASEURL . "/Record/attachment/" . $targetId;
          }
        } else {
          throw new ToolCallException("❌ Gagal Update item '{$item['barang']}' ke database.");
        }
      }

      $linkSuffix = isset($attachmentLink) ? $attachmentLink : "";
      return "✅ Berhasil! " . count($results) . " Transaksi diperbarui: " . implode(";\n", $results) . $linkSuffix;
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar kategori/kelompok transaksi yang sudah ada
   * Gunakan tool ini untuk referensi saat mengisi field "kelompok" di catat_transaksi.
   */
  #[McpTool(
    name: 'get_kelompok',
    description: 'Mendapatkan daftar kategori/kelompok transaksi yang sudah ada, dipecah per status rutin. Format data [kelompok,rutin,count].
    Setiap kelompok bisa muncul hingga 2 baris (satu untuk rutin=true, satu untuk rutin=false) — count menunjukkan berapa kali kelompok itu tercatat dengan status rutin tersebut.
    GUNAKAN INI SEBAGAI SINYAL TAMBAHAN untuk menentukan field "rutin" saat mencatat transaksi: jika suatu kelompok historisnya dominan rutin=true (count rutin=true jauh lebih besar), item baru di kelompok yang sama kemungkinan besar rutin=true juga, dan sebaliknya. Sinyal historis ini TIDAK menggantikan urutan prioritas di system_prompt (bagian RUTIN vs NON-RUTIN) — pakai untuk menajamkan keputusan pada kasus ambigu di rule 5/6, bukan untuk membatalkan rule 1-4 (event, tagihan bulanan esensial, langganan non-esensial, hari Minggu).',
    annotations: new ToolAnnotations(
      readOnlyHint: true,
      openWorldHint: false
    ),
    outputSchema: [
      'type' => 'object',
      'properties' => [
        'data' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'kelompok' => ['type' => 'string'],
              'rutin'    => ['type' => 'boolean', 'description' => 'Status rutin untuk baris hitungan ini'],
              'count'    => ['type' => 'integer']
            ]
          ]
        ]
      ]
    ]
  )]
  public function getKelompok(): array
  {
    try {
      $rows = new ModelsTransaksi()->getKelompok();
      return [
        'data' => array_map(
          fn($row) => [...$row, 'rutin' => $row['rutin'] == 1],
          $rows
        )
      ];
    } catch (\Exception $e) {
      throw new ResourceReadException("Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar transaksi dalam rentang tanggal tertentu
   * Gunakan tool ini untuk mendapatkan data transaksi dalam format yang mudah dipahami untuk analisis
   */
  #[McpTool(
    name: 'get_transaksi',
    description: 'Mendapatkan daftar transaksi dalam rentang tanggal tertentu ',
    annotations: new ToolAnnotations(
      readOnlyHint: true,
      openWorldHint: false
    ),
    outputSchema: [
      'type' => 'object',
      'properties' => [
        'data' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'id'                   => ['type' => 'integer'],
              'jenis_transaksi'      => ['type' => 'string'],
              'harta'                => ['type' => 'boolean'],
              'barang'               => ['type' => 'string'],
              'rekening_sumber'      => ['type' => ['integer', 'null']],
              'rekening_masuk'       => ['type' => ['integer', 'null']],
              'nominal'              => ['type' => 'number'],
              'nominal_asing'        => ['type' => 'number'],
              'kuantitas'            => ['type' => 'number'],
              'penyusutan_bunga'     => ['type' => 'number'],
              'rutin'                => ['type' => 'boolean'],
              'kelompok'             => ['type' => ['string', 'null']],
              'tanggal'              => ['type' => 'string', 'format' => 'date'],
              'relasi_transaksi'     => ['type' => ['integer', 'null']],
              'attachment'           => ['type' => ['string', 'null']],
              'keterangan'           => ['type' => ['string', 'null']],
              'review'               => ['type' => ['string', 'null']],
              'created_at'           => ['type' => 'string', 'format' => 'date-time'],
              'nama_rekening_sumber' => ['type' => ['string', 'null']],
              'nama_rekening_masuk'  => ['type' => ['string', 'null']],
              'jenis_budget_sumber'  => ['type' => ['string', 'null']],
              'jenis_budget_masuk'   => ['type' => ['string', 'null']]
            ]
          ]
        ]
      ]
    ]
  )]
  #[Schema(
    properties: [
      'startDate'           => [
        'type' => ['string', 'null'],
        'format' => 'date',
        'description' => 'Format: YYYY-MM-DD'
      ],
      'endDate'           => [
        'type' => ['string', 'null'],
        'format' => 'date',
        'description' => 'Format: YYYY-MM-DD'
      ],
    ]
  )]
  public function getTransaksi(
    ?string $startDate = null,
    ?string $endDate = null,
  ): array {
    $startDate = $startDate ?? date('Y-m-01'); // Default ke tanggal 1
    $endDate = $endDate ?? date('Y-m-d'); // Default ke hari ini
    try {
      return [
        'data' => (new ModelsTransaksi())->getInRange($startDate, $endDate)
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }
  /**
   * Mencari transaksi berdasarkan kriteria tertentu
   * Gunakan tool ini untuk mendapatkan data transaksi dalam format yang mudah dipahami untuk analisis
   */
  #[McpTool(
    name: 'search_Transaksi',
    description: 'Mencari transaksi berdasarkan kriteria tertentu',
    annotations: new ToolAnnotations(
      readOnlyHint: true,
      openWorldHint: false
    ),
    outputSchema: [
      'type' => 'object',
      'properties' => [
        'data' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'id'                   => ['type' => 'integer'],
              'jenis_transaksi'      => ['type' => 'string'],
              'harta'                => ['type' => 'boolean'],
              'barang'               => ['type' => 'string'],
              'rekening_sumber'      => ['type' => ['integer', 'null']],
              'rekening_masuk'       => ['type' => ['integer', 'null']],
              'nominal'              => ['type' => 'number'],
              'nominal_asing'        => ['type' => 'number'],
              'kuantitas'            => ['type' => 'number'],
              'penyusutan_bunga'     => ['type' => 'number'],
              'rutin'                => ['type' => 'boolean'],
              'kelompok'             => ['type' => ['string', 'null']],
              'tanggal'              => ['type' => 'string', 'format' => 'date'],
              'relasi_transaksi'     => ['type' => ['integer', 'null']],
              'attachment'           => ['type' => ['string', 'null']],
              'keterangan'           => ['type' => ['string', 'null']],
              'review'               => ['type' => ['string', 'null']],
              'created_at'           => ['type' => 'string', 'format' => 'date-time'],
              'nama_rekening_sumber' => ['type' => ['string', 'null']],
              'nama_rekening_masuk'  => ['type' => ['string', 'null']],
              'jenis_budget_sumber'  => ['type' => ['string', 'null']],
              'jenis_budget_masuk'   => ['type' => ['string', 'null']]
            ]
          ]
        ]
      ]
    ]
  )]
  #[Schema(
    properties: [
      'search'           => [
        'type' => ['string'],
        'description' => 'Search By Id, Name, Kelompok'
      ],
    ]
  )]
  public function searchTransaksi(
    string $search,
  ): array {
    try {
      return [
        'data' => new ModelsTransaksi()->find($search)
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }
}
