<?php

namespace App\libs;

use Mcp\Capability\Attribute\{McpTool, Schema};
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use App\models\Transaksi;

class tools
{
  /**
   * Mencatat transaksi keuangan baru (Pemasukan, Pengeluaran, atau Pindah Buku)
   */
  #[McpTool(
    name: 'catat_transaksi',
    description: 'Mencatat transaksi keuangan baru (Pemasukan, Pengeluaran, atau Pindah Buku)
    ATURAN PENTING:
    1. STRUK BELANJA: Jika input berupa struk dengan banyak item, JANGAN dicatat sebagai satu total. Pecah menjadi item individu. Catat item pertama, ambil ID-nya dari response, lalu gunakan ID tersebut sebagai "relasi_transaksi" untuk item-item berikutnya dalam struk yang sama.
    2. TRANSAKSI HARTA:
      - Pastikan terlebih dahulu terdapat transaksi pengeluaran dari rekening untuk membeli lalu buatkan transaksi pemasukan ke rekening harta dengan nilai barang yang telah dibeli. Relasikan transaksi Harta ke Transaksi Pembelian
      - Bila penjualan maka Pengeluaran di Rekening Harta Terkait, lalu pemasukan
    3. NOMINAL ASING: Gunakan "nominal_asing" jika transaksi melibatkan Emas (dalam Gram) atau mata uang asing seperti USD (Paypal).
    4. VALIDASI: Selalu gunakan tool "get_rekening" untuk memastikan ID rekening sumber/masuk sudah tepat sebelum mencatat.
    5. DISKON:
      - Jika diskon per item: Catat harga NETTO (setelah diskon).
      - Jika diskon total di akhir struk: Gunakan metode PRORATA (bagi diskon ke setiap item secara proporsional) agar total pengeluaran sesuai dengan nominal yang dibayarkan di kasir.
    6. ASSET LOGIC:
      - Pembelian = Pengeluaran (rekening uang) + Pemasukan (Harta, harta=true, isi penyusutan_bunga), relasikan keduanya.
      - Penjualan/Pembuangan = Pengeluaran (Harta, harta=true) + Pemasukan (rekening uang, bila ada hasil jual), relasikan keduanya.
      - DILARANG: harta=true pada Pengeluaran dari rekening uang biasa.
      - DILARANG: Pindah Buku untuk Harta.
    7. KURS LOGIC: Perubahan nilai tukar dicatat sebagai Pemasukan/Pengeluaran pada kolom nominal (selisihnya), dengan nominal_asing = 0.',
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
        'description' => 'Daftar transaksi yang akan dicatat. Berupa array of objects berisi detail transaksi.',
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
              'description' => 'ID Rekening asal. Gunakan tool get_rekening untuk mencari ID yang tepat. (Wajib jika Pengeluaran/Pindah Buku)
                  ATURAN PINDAH BUKU:
                  1. Tidak boleh sama dengan rekening_masuk.
                  2. Dilarang menggunakan rekening tipe HARTA (Aset).
                  3. Jika rekening asal adalah mata uang ASING (Emas/USD), maka rekening tujuan HARUS memiliki jenis mata uang asing yang sama.'
            ],
            'rekening_masuk'    => [
              'type' => ['integer', 'null'],
              'description' => 'ID Rekening tujuan. Gunakan tool get_rekening untuk mencari ID yang tepat. (Wajib jika Pemasukan/Pindah Buku)
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
              'description' => 'KLASIFIKASI RUTINITAS:
                - TRUE: (Pengeluaran Rutin) Pengeluaran harian yang mendukung kerja/hidup dasar.
                - FALSE: (Pengeluaran Non/Tidak Rutin) Pengeluaran yang tidak terjadi setiap minggu/bulan, atau bagian dari event khusus.'
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
    $transaksi = new Transaksi();
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
            'rekening_sumber'   => ['type' => ['integer', 'null'], 'description' => 'ID Rekening asal.'],
            'rekening_masuk'    => ['type' => ['integer', 'null'], 'description' => 'ID Rekening tujuan.'],
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
    $transaksi = new Transaksi();
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
}
