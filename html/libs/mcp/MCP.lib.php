<?php

namespace App\libs;

use Mcp\Capability\Attribute\{McpTool, Schema, McpResource};
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use App\models\Transaksi;
use App\models\Rekening;

class MCP
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
        'status' => ['type' => 'string']
      ]
    ]
  )]
  #[Schema(
    properties: [
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
        'description' => 'Jumlah dalam Rupiah. Untuk selisih kurs: isi selisih nilainya di sini, set nominal_asing = 0.'
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
          - TRUE: Pengeluaran operasional harian yang mendukung kerja/hidup dasar (Senin-Sabtu).
          - FALSE: Pengeluaran yang tidak terjadi setiap minggu/bulan, atau bagian dari event khusus.'
      ],
      'kelompok'          => [
        'type' => ['string', 'null'],
        'default' => null,
        'description' => 'Kategori transaksi. Gunakan get_kelompok untuk referensi saja tidak wajib diikuti, boleh buat baru sesuai kebutuhan. Aturan penentuan kelompok:
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
      'attachment_base64' => [
        'type' => ['string', 'null'],
        'description' => 'Optional: Base64 string of receipt image. Untuk Struk masukan saja ke transaksi utama (item pertama), tidak perlu untuk item berikutnya yang menggunakan relasi_transaksi.'
      ]
    ],
    required: ['jenis_transaksi', 'barang', 'nominal', 'kuantitas', 'tanggal']
  )]
  public function catatTransaksi(
    string $jenis_transaksi,
    string $barang,
    float $nominal,
    float $kuantitas,
    string $tanggal,
    ?int $rekening_sumber = null,
    ?int $rekening_masuk = null,
    ?bool $harta = false,
    ?float $nominal_asing = 0,
    ?float $penyusutan_bunga = 0,
    ?bool $rutin = false,
    ?string $kelompok = null,
    ?int $relasi_transaksi = null,
    ?string $keterangan = null,
    ?string $attachment_base64 = null
  ): string {
    try {
      // 1. Handle File via your logic if AI provided it
      $finalFileName = null;
      if (!empty($attachment_base64)) {
        $binaryData = base64_decode($attachment_base64);
        $finalFileName = $this->processFile($binaryData, 'ai_upload.jpg');
      }
      $data = [
        'jenis_transaksi'   => $jenis_transaksi,
        'harta'             => $harta,
        'barang'            => $barang,
        'rekening_sumber'   => $rekening_sumber,
        'rekening_masuk'    => $rekening_masuk,
        'nominal'           => $nominal,
        'nominal_asing'     => $nominal_asing,
        'kuantitas'         => $kuantitas,
        'penyusutan_bunga'  => $penyusutan_bunga,
        'rutin'             => $rutin,
        'kelompok'          => $kelompok,
        'tanggal'           => $tanggal,
        'relasi_transaksi'  => $relasi_transaksi,
        'attachment'        => $finalFileName,
        'keterangan'        => $keterangan,
      ];
      $trasaksi = new Transaksi();
      $result = $trasaksi->insertTransaksi($data);

      return ($result > 0)
        ? "✅ Berhasil! Transaksi Id #{$trasaksi->lastInsertId()} telah dicatat."
        : throw new ToolCallException("❌ Gagal menyimpan ke database.");
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
  /**
   * Mencatat transaksi keuangan baru (Pemasukan, Pengeluaran, atau Pindah Buku)
   */
  #[McpTool(
    name: 'catat_transaksi_masal',
    description: 'Mencatat transaksi keuangan baru secara massal dengan batas maksimal 15 transaksi per panggilan. Input berupa array of objects dengan format yang sama seperti catat_transaksi',
    annotations: new ToolAnnotations(
      readOnlyHint: false,
      destructiveHint: false,
      idempotentHint: false,
      openWorldHint: false
    ),
    outputSchema: [
      'type' => 'object',
      'properties' => [
        'status' => ['type' => 'string']
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
            'attachment_base64' => ['type' => ['string', 'null']]
          ],
          'required' => ['jenis_transaksi', 'barang', 'nominal', 'kuantitas', 'tanggal']
        ]
      ],
      'autoRelate' => ['type' => 'boolean', 'default' => false]
    ],
    required: ['data']
  )]
  public function catatTransaksiMasal(array $data, bool $autoRelate = false): string
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

        // 2. Handle File Attachment
        $finalFileName = null;
        if (!empty($item['attachment_base64'])) {
          $binaryData = base64_decode($item['attachment_base64']);
          $finalFileName = $this->processFile($binaryData, 'ai_upload_' . uniqid() . '.jpg');
        }

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
        } else {
          throw new ToolCallException("❌ Gagal menyimpan item '{$item['barang']}' ke database.");
        }
      }

      return "✅ Berhasil! " . count($results) . " Transaksi dicatat: " . implode(', ', $results);
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar rekening dan ID untuk input transaksi
   * Gunakan tool ini untuk mendapatkan ID rekening yang valid saat mencatat transaksi baru.
   */
  #[McpTool(
    name: 'get_rekening',
    description: 'Mendapatkan daftar rekening dan ID untuk input transaksi
    Dengan format data [rekening_id,saldo,saldo_asing,aktif,harta,isAsing]
  ',
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
              'rekening_id'   => ['type' => 'integer'],
              'nama_rekening' => ['type' => 'string'],
              'saldo'         => ['type' => 'number'],
              'saldo_asing'   => ['type' => 'number'],
              'aktif'         => ['type' => 'boolean'],
              'harta'         => ['type' => 'boolean'],
              'isAsing'       => ['type' => 'boolean']
            ]
          ]
        ]
      ]
    ]
  )]
  public function getRekening(): array
  {
    try {
      // FIX: Wrap the list in a key so the result is a 'record' (JSON Object)
      return [
        'data' => new Rekening()->getAll()
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar kategori/kelompok transaksi yang sudah ada
   * Gunakan tool ini untuk referensi saat mengisi field "kelompok" di catat_transaksi.
   */
  #[McpTool(
    name: 'get_kelompok',
    description: 'Mendapatkan daftar kategori/kelompok transaksi yang sudah ada Dengan format data [kelompok,count]',
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
      return [
        'data' => new Transaksi()->getKelompok()
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar transaksi dalam rentang tanggal tertentu
   * Gunakan tool ini untuk mendapatkan data transaksi dalam format yang mudah dipahami untuk analisis
   */
  #[McpTool(
    name: 'get_transaksi',
    description: 'Mendapatkan daftar transaksi dalam rentang tanggal tertentu Dengan format data [id,jenis_transaksi,harta,barang,rekening_sumber,rekening_masuk,nominal,nominal_asing,kuantitas,penyusutan_bunga,rutin,kelompok,tanggal,relasi_transaksi,attachment,keterangan,review,created_at,nama_rekening_sumber,nama_rekening_masuk,jenis_budget_sumber,jenis_budget_masuk]',
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
        'data' => (new Transaksi())->getInRange($startDate, $endDate)
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
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
        'status' => ['type' => 'string']
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
          // This now happens in memory, making it lightning fast
          $transaksi->updateTransaksi($updateData, ['id' => $targetId]);
          $results[] = "#{$targetId} ({$item['barang']})";
        } else {
          throw new ToolCallException("❌ Gagal Update item '{$item['barang']}' ke database.");
        }
      }

      return "✅ Berhasil! " . count($results) . " Transaksi diperbarui: " . implode(', ', $results);
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar harta/aset yang sudah ada dan saldo pembukuannya
   * Gunakan tool ini untuk referensi saat mencatat transaksi yang melibatkan aset permanen seperti HP, Motor, Emas, Furnitur. Tool ini akan menampilkan semua rekening dengan tipe harta
   */
  #[McpTool(
    name: 'get_Harta',
    description: 'Mendapatkan daftar harta/aset yang sudah ada dan saldo pembukuannya Dengan format data [kelompok,count]',
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
              'created_at'           => ['type' => 'string', 'format' => 'date-time']
            ]
          ]
        ]
      ]
    ]
  )]
  public function getHarta(): array
  {
    try {
      return [
        'data' => new Transaksi()->getDaftarHarta()
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }


  /*  */
  private function processFile($fileSource, $fileName, $delete_old = null)
  {
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
      throw new \Exception('Tipe file invalid.');
    }

    $name = uniqid('file_') . '.' . $ext;
    $uploadDir = dirname(__DIR__) . '/uploads/';
    $path = $uploadDir . $name;

    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }

    // Check if it's an uploaded file (from $_FILES) or a string (from AI/Base64)
    $success = is_uploaded_file($fileSource)
      ? move_uploaded_file($fileSource, $path)
      : file_put_contents($path, $fileSource);

    if ($success) {
      if (!empty($delete_old) && file_exists($uploadDir . $delete_old)) {
        unlink($uploadDir . $delete_old);
      }
      return $name;
    }
    throw new \Exception('Upload error.');
  }
}
