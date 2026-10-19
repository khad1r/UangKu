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
        'data' => new ModelsRekening()->getAll()
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
      throw new ResourceReadException("Error: " . $e->getMessage());
    }
  }
}
