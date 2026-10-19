<?php

namespace App\libs;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ResourceReadException;
use Mcp\Schema\ToolAnnotations;
use App\models\Rekening as ModelsRekening;
use App\models\Transaksi;

class rekening
{
  /**
   * Mendapatkan daftar rekening dan ID untuk input transaksi
   * Gunakan tool ini untuk mendapatkan ID rekening yang valid saat mencatat transaksi baru.
   */
  #[McpTool(
    name: 'get_rekening',
    description: 'Mendapatkan daftar rekening aktif dan ID untuk input transaksi. WAJIB dipanggil fresh sebelum mencatat transaksi untuk mendapatkan ID rekening valid terkini (jangan gunakan ID dari ingatan/cache).
    Format data: [rekening_id, nama_rekening, saldo, saldo_asing, aktif, harta, isAsing].',
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
      $rows = (new ModelsRekening())->getAll();
      return [
        'data' => array_map(fn($r) => [
          'rekening_id'   => (int) $r['id'],
          'nama_rekening' => (string) $r['nama'],
          'saldo'         => (float) ($r['saldo'] ?? 0),
          'saldo_asing'   => (float) ($r['saldo_asing'] ?? 0),
          'aktif'         => !empty($r['aktif']),
          'harta'         => !empty($r['harta']),
          'isAsing'       => !empty($r['isAsing']),
        ], $rows)
      ];
    } catch (\Exception $e) {
      throw new ResourceReadException("Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar harta/aset yang sudah ada dan saldo pembukuannya
   * Gunakan tool ini untuk referensi saat mencatat transaksi yang melibatkan aset permanen seperti HP, Motor, Emas, Furnitur. Tool ini akan menampilkan semua rekening dengan tipe harta
   */
  #[McpTool(
    name: 'get_Harta',
    description: 'Mendapatkan daftar transaksi harta/aset yang sudah tercatat dan nilai pembukuannya (aset permanen seperti HP, Kendaraan, Emas, Properti, Furnitur) untuk referensi inventaris dan depresiasi.',
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
              'rekening_sumber'      => ['anyOf' => [['type' => 'integer'], ['type' => 'null']]],
              'rekening_masuk'       => ['anyOf' => [['type' => 'integer'], ['type' => 'null']]],
              'nominal'              => ['type' => 'number'],
              'nominal_asing'        => ['type' => 'number'],
              'kuantitas'            => ['type' => 'number'],
              'penyusutan_bunga'     => ['type' => 'number'],
              'rutin'                => ['type' => 'boolean'],
              'kelompok'             => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'tanggal'              => ['type' => 'string', 'format' => 'date'],
              'relasi_transaksi'     => ['anyOf' => [['type' => 'integer'], ['type' => 'null']]],
              'attachment'           => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'keterangan'           => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'review'               => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'created_at'           => ['type' => 'string', 'format' => 'date']
            ]
          ]
        ]
      ]
    ]
  )]
  public function getHarta(): array
  {
    try {
      $rows = (new Transaksi())->getDaftarHarta();
      return [
        'data' => array_map(fn($row) => [
          'id'               => (int) $row['id'],
          'jenis_transaksi'  => (string) $row['jenis_transaksi'],
          'harta'            => !empty($row['harta']),
          'barang'           => (string) $row['barang'],
          'rekening_sumber'  => isset($row['rekening_sumber']) && $row['rekening_sumber'] !== '' ? (int) $row['rekening_sumber'] : null,
          'rekening_masuk'   => isset($row['rekening_masuk']) && $row['rekening_masuk'] !== '' ? (int) $row['rekening_masuk'] : null,
          'nominal'          => (float) $row['nominal'],
          'nominal_asing'    => isset($row['nominal_asing']) ? (float) $row['nominal_asing'] : 0.0,
          'kuantitas'        => isset($row['kuantitas']) ? (float) $row['kuantitas'] : 1.0,
          'penyusutan_bunga' => isset($row['penyusutan_bunga']) ? (float) $row['penyusutan_bunga'] : 0.0,
          'rutin'            => !empty($row['rutin']),
          'kelompok'         => $row['kelompok'] ?? null,
          'tanggal'          => (string) $row['tanggal'],
          'relasi_transaksi' => isset($row['relasi_transaksi']) && $row['relasi_transaksi'] !== '' ? (int) $row['relasi_transaksi'] : null,
          'attachment'       => $row['attachment'] ?? null,
          'keterangan'       => $row['keterangan'] ?? null,
          'review'           => $row['review'] ?? null,
          'created_at'       => !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : date('Y-m-d'),
        ], $rows)
      ];
    } catch (\Exception $e) {
      throw new ResourceReadException("Error: " . $e->getMessage());
    }
  }
}
