<?php

namespace App\libs;

use Mcp\Capability\Attribute\{McpTool, Schema, McpResource};
use Mcp\Exception\ToolCallException;
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
    2. TRANSAKSI HARTA: Set "harta" = true jika transaksi melibatkan aset permanen/barang berharga (HP, Motor, Emas, Furnitur). Gunakan "harta" = false untuk barang habis pakai (Makanan, Bensin, Listrik).
    3. NOMINAL ASING: Gunakan "nominal_asing" jika transaksi melibatkan Emas (dalam Gram) atau mata uang asing seperti USD (Paypal).
    4. VALIDASI: Selalu gunakan tool "get_rekening" untuk memastikan ID rekening sumber/masuk sudah tepat sebelum mencatat.
    5. DISKON:
      - Jika diskon per item: Catat harga NETTO (setelah diskon).
      - Jika diskon total di akhir struk: Gunakan metode PRORATA (bagi diskon ke setiap item secara proporsional) agar total pengeluaran sesuai dengan nominal yang dibayarkan di kasir.
    6. ASSET LOGIC: Dilarang Pindah Buku untuk Harta. Pembelian = Pengeluaran (Bank) + Pemasukan (Harta ID 16) dengan penyusutan_bunga. Penjualan = Pengeluaran (Harta) + Pemasukan (Bank).
    7. KURS LOGIC: Perubahan nilai tukar dicatat sebagai Pemasukan/Pengeluaran pada kolom nominal (selisihnya), dengan nominal_asing = 0.'
  )]
  #[Schema(
    properties: [
      'jenis_transaksi'   => [
        'type' => 'string',
        'enum' => ['Pengeluaran', 'Pemasukan', 'Pindah Buku'],
        'description' => 'Tipe transaksi'
      ],
      'harta'             => ['type' => 'boolean', 'description' => 'Set TRUE untuk aset permanen (HP, Motor, Emas, Furnitur). Set FALSE untuk barang habis pakai.'],
      'barang'            => ['type' => 'string', 'description' => 'Nama barang atau deskripsi singkat'],
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
        'description' => 'Persentase penyusutan buku per tahun. Hanya diisi jika jenis_transaksi adalah Pemasukan dan harta = true dan Jenis Rekening adalah Harta, untuk mencatat penyusutan bunga aset (misal: bunga deposito).'
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
        'description' => 'Kategori transaksi. Gunakan get_kelompok untuk referensi.
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
      'keterangan'        => ['type' => ['string', 'null'], 'default' => ''],
      'attachment_base64' => ['type' => ['string', 'null'], 'description' => 'Optional: Base64 string of receipt image']
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
   * Mendapatkan daftar rekening dan ID untuk input transaksi
   * Gunakan tool ini untuk mendapatkan ID rekening yang valid saat mencatat transaksi baru.
   */
  #[McpTool(name: 'get_rekening', description: 'Mendapatkan daftar rekening dan ID untuk input transaksi
    Dengan format data [rekening_id,saldo,saldo_asing,aktif,harta,isAsing]
  ')]
  public function getRekening(): array
  {
    try {
      $list = (new Rekening())->getAll();
      // FIX: Wrap the list in a key so the result is a 'record' (JSON Object)
      return [
        'data' => $list
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar kategori/kelompok transaksi yang sudah ada
   * Gunakan tool ini untuk referensi saat mengisi field "kelompok" di catat_transaksi.
   */
  #[McpTool(name: 'get_kelompok', description: 'Mendapatkan daftar kategori/kelompok transaksi yang sudah ada
    Dengan format data [kelompok,count]
  ')]
  public function getKelompok(): array
  {
    try {
      $list = (new Transaksi())->getKelompok();
      // FIX: Wrap the list in a key so the result is a 'record' (JSON Object)
      return [
        'data' => $list
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }

  /**
   * Mendapatkan daftar harta/aset yang sudah ada dan saldo pembukuannya
   * Gunakan tool ini untuk referensi saat mencatat transaksi yang melibatkan aset permanen seperti HP, Motor, Emas, Furnitur. Tool ini akan menampilkan semua rekening dengan tipe
   */
  /*  #[McpTool(name: 'get_Harta', description: 'Mendapatkan daftar harta/aset yang sudah ada dan saldo pembukuannya
    Dengan format data [kelompok,count]
  ')]*/

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
