<?php

namespace App\libs;

use Mcp\Capability\Attribute\{McpTool, Schema};
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use App\models\Wishlist as ModelsWishlist;

class wishlist
{
  /**
   * Menambahkan barang baru ke Wishlist
   * Wishlist adalah daftar terpisah dari TRANSAKSI — untuk barang yang sedang dipertimbangkan
   * untuk dibeli nanti, bukan pencatatan transaksi keuangan yang sudah terjadi.
   */
  #[McpTool(
    name: 'catat_wishlist',
    description: 'Menambahkan barang baru ke Wishlist (daftar barang yang sedang dipertimbangkan untuk dibeli nanti, TERPISAH dari pencatatan transaksi keuangan). Gunakan ini ketika user ingin "menyimpan dulu" atau "pikir-pikir dulu" sebuah rencana pembelian, BUKAN untuk mencatat pembelian yang sudah terjadi (pakai catat_transaksi untuk itu).',
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
      'nama'            => ['type' => 'string', 'description' => 'Nama barang'],
      'harga_estimasi'  => ['type' => ['number', 'null'], 'description' => 'Estimasi harga barang dalam Rupiah (opsional)'],
      'link'            => ['type' => ['string', 'null'], 'description' => 'Link/URL ke halaman produk (opsional)'],
      'catatan'         => ['type' => ['string', 'null'], 'description' => 'Catatan atau pertimbangan tentang barang ini, bisa diperbarui kapan saja lewat update_wishlist'],
    ],
    required: ['nama']
  )]
  public function catatWishlist(string $nama, ?float $harga_estimasi = null, ?string $link = null, ?string $catatan = null): string
  {
    try {
      $model = new ModelsWishlist();
      $result = $model->insertWishlist([
        'nama'            => $nama,
        'harga_estimasi'  => $harga_estimasi,
        'link'            => $link,
        'catatan'         => $catatan,
      ]);
      if ($result > 0) {
        return "✅ Wishlist #{$model->lastInsertId()} ({$nama}) ditambahkan.";
      }
      throw new ToolCallException("❌ Gagal menambahkan '{$nama}' ke Wishlist.");
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan seluruh isi Wishlist
   */
  #[McpTool(
    name: 'get_wishlist',
    description: 'Mendapatkan seluruh isi Wishlist (barang yang sedang dipertimbangkan untuk dibeli, belum dicatat sebagai transaksi).',
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
              'id'              => ['type' => 'integer'],
              'nama'            => ['type' => 'string'],
              'harga_estimasi'  => ['type' => ['number', 'null']],
              'link'            => ['type' => ['string', 'null']],
              'catatan'         => ['type' => ['string', 'null']],
              'created_at'      => ['type' => 'string', 'format' => 'date-time'],
              'updated_at'      => ['type' => ['string', 'null'], 'format' => 'date-time'],
            ]
          ]
        ]
      ]
    ]
  )]
  public function getWishlist(): array
  {
    try {
      return [
        'data' => new ModelsWishlist()->getAll()
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }
  /**
   * Memperbarui item Wishlist berdasarkan ID (misal: mengubah catatan setelah dipertimbangkan lagi)
   */
  #[McpTool(
    name: 'update_wishlist',
    description: 'Memperbarui item Wishlist berdasarkan ID — misal mengubah catatan pertimbangan, estimasi harga, atau link. Hanya field yang diisi yang akan diubah.',
    annotations: new ToolAnnotations(
      readOnlyHint: false,
      destructiveHint: false,
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
      'id'              => ['type' => 'integer', 'description' => 'ID item Wishlist yang akan diperbarui'],
      'nama'            => ['type' => ['string', 'null']],
      'harga_estimasi'  => ['type' => ['number', 'null']],
      'link'            => ['type' => ['string', 'null']],
      'catatan'         => ['type' => ['string', 'null']],
    ],
    required: ['id']
  )]
  public function updateWishlist(int $id, ?string $nama = null, ?float $harga_estimasi = null, ?string $link = null, ?string $catatan = null): string
  {
    try {
      $model = new ModelsWishlist();
      if (empty($model->getById($id))) {
        throw new ToolCallException("❌ Item Wishlist #{$id} tidak ditemukan.");
      }
      $update = array_filter([
        'nama'            => $nama,
        'harga_estimasi'  => $harga_estimasi,
        'link'            => $link,
        'catatan'         => $catatan,
      ], fn($v) => $v !== null);
      $model->updateWishlist($update, ['id' => $id]);
      return "✅ Wishlist #{$id} diperbarui.";
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
  /**
   * Menghapus item Wishlist berdasarkan ID
   */
  #[McpTool(
    name: 'delete_wishlist',
    description: 'Menghapus item dari Wishlist berdasarkan ID.',
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
      'id' => ['type' => 'integer', 'description' => 'ID item Wishlist yang akan dihapus'],
    ],
    required: ['id']
  )]
  public function deleteWishlist(int $id): string
  {
    try {
      if (new ModelsWishlist()->deleteWishlist($id) > 0) {
        return "✅ Wishlist #{$id} dihapus.";
      }
      throw new ToolCallException("❌ Item Wishlist #{$id} tidak ditemukan.");
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
}
