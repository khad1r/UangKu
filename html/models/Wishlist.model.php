<?php

namespace App\models;

use App\Database;

class Wishlist extends Database
{
  private $table = "WISHLIST";
  public function __construct()
  {
    parent::__construct();
  }
  public function getById(string|int $id)
  {
    return $this
      ->query("SELECT * FROM {$this->table} WHERE id=:id")
      ->bind('id', $id)
      ->resultSingle();
  }
  public function getAll()
  {
    return $this
      ->query("SELECT * FROM {$this->table} ORDER BY created_at DESC")
      ->resultSet();
  }
  public function insertWishlist($data)
  {
    return $this->insert($this->table, $data)->affectedRows();
  }
  public function updateWishlist($data, $where)
  {
    $data['updated_at'] = date('Y-m-d H:i:s');
    return $this->update($this->table, $data, $where)->affectedRows();
  }
  public function deleteWishlist(string|int $id)
  {
    return $this
      ->query("DELETE FROM {$this->table} WHERE id=:id")
      ->bind('id', $id)
      ->execute()->affectedRows();
  }
}
