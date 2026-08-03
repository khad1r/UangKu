<?php

namespace App\Controllers;

use App\Controller;
use App\models\Wishlist as ModelsWishlist;
use App\Route;

class Wishlist extends Controller
{
  public function __construct()
  {
    parent::__construct();
    if (!CheckUser()) {
      showAlert('Akses Ditolak', 'warning');
      Route::Redirect('/Auth/Logout');
      exit;
    }
  }
  public function index()
  {
    $data['title'] = 'Wishlist';
    $data['subTitle'] = '<i class="fas fa-star"></i> <strong><u>Wishlist</u></strong> <i class="fas fa-gift"></i>';
    $data['items'] = new ModelsWishlist()->getAll();
    setCacheControl(0);
    $data['view'] = 'wishlist/list';
    $data['top-left-view'] = 'components/header';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }
  public function add()
  {
    if (!empty($_POST)) {
      try {
        sanitize_input($_POST);
        validate_required_input($_POST, ['nama']);
        $insert = [
          'nama'            => $_POST['nama'],
          'harga_estimasi'  => $_POST['harga_estimasi'] !== '' ? $_POST['harga_estimasi'] : null,
          'link'            => $_POST['link'] ?? null,
          'catatan'         => $_POST['catatan'] ?? null,
        ];
        if (new ModelsWishlist()->insertWishlist($insert) > 0) {
          showAlert("Item Wishlist Berhasil Ditambahkan", 'success');
          Route::Redirect('/Wishlist');
          return;
        }
        showAlert('Operasi Gagal', 'danger');
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
      }
    }
    Route::Redirect('/Wishlist');
  }
  public function edit($id = '')
  {
    if (!empty($_POST)) {
      try {
        sanitize_input($_POST);
        validate_required_input($_POST, ['nama']);
        $update = [
          'nama'            => $_POST['nama'],
          'harga_estimasi'  => $_POST['harga_estimasi'] !== '' ? $_POST['harga_estimasi'] : null,
          'link'            => $_POST['link'] ?? null,
          'catatan'         => $_POST['catatan'] ?? null,
        ];
        if (new ModelsWishlist()->updateWishlist($update, ['id' => $id]) > 0) {
          showAlert("Item Wishlist Berhasil Diperbarui", 'success');
          Route::Redirect('/Wishlist');
          return;
        }
        showAlert('Operasi Gagal', 'danger');
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
      }
    }
    Route::Redirect('/Wishlist');
  }
  public function delete()
  {
    try {
      if (new ModelsWishlist()->deleteWishlist($_POST['id']) > 0) {
        showAlert("Item Wishlist Berhasil Dihapus", 'warning');
      } else {
        throw new \Exception("Item tidak ditemukan.", 1);
      }
    } catch (\Exception $e) {
      showAlert($e->getMessage(), 'danger');
    }
    Route::Referer('/Wishlist');
  }
}
