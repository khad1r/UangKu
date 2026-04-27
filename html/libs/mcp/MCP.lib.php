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
  #[McpTool(name: 'catat_transaksi', description: 'Mencatat transaksi keuangan baru (Pemasukan, Pengeluaran, atau Pindah Buku)')]
  #[Schema(
    properties: [
      'jenis_transaksi'   => [
        'type' => 'string',
        'enum' => ['Pengeluaran', 'Pemasukan', 'Pindah Buku'],
        'description' => 'Tipe transaksi'
      ],
      'harta'             => ['type' => 'boolean', 'description' => 'Apakah ini transaksi aset/harta?'],
      'barang'            => ['type' => 'string', 'description' => 'Nama barang atau deskripsi singkat'],
      'rekening_sumber'   => [
        'type' => ['integer', 'null'],
        'description' => 'ID Rekening asal. Gunakan tool get_rekening untuk mencari ID yang tepat. (Wajib jika Pengeluaran/Pindah Buku)'
      ],
      'rekening_masuk'    => [
        'type' => ['integer', 'null'],
        'description' => 'ID Rekening tujuan. Gunakan tool get_rekening untuk mencari ID yang tepat. (Wajib jika Pemasukan/Pindah Buku)'
      ],
      'nominal'           => [
        'type' => 'number',
        'description' => 'Jumlah uang dalam angka'
      ],
      'nominal_asing'     => ['type' => ['number', 'null']],
      'kuantitas'         => ['type' => 'number', 'default' => 1],
      'penyusutan_bunga'  => ['type' => 'number', 'default' => 0],
      'rutin'             => ['type' => 'boolean', 'default' => false],
      'kelompok'          => [
        'type' => ['string', 'null'],
        'description' => 'Kategori transaksi. Gunakan get_kelompok untuk referensi.'
      ],
      'tanggal'           => [
        'type' => ['string', 'null'],
        'format' => 'date',
        'description' => 'Format: YYYY-MM-DD'
      ],
      'relasi_transaksi'  => ['type' => ['integer', 'null'], 'description' => 'ID transaksi lain yang berhubungan'],
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
  #[McpTool(name: 'get_rekening', description: 'Mendapatkan daftar rekening dan ID untuk input transaksi')]
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
  #[McpTool(name: 'get_kelompok', description: 'Mendapatkan daftar kategori/kelompok transaksi yang sudah ada')]
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
