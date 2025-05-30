<?php

namespace App\models;

use App\Database;

class virtualAccount extends Database
{
  public function __construct()
  {
    parent::__construct('cbs');
  }
  public function findVirtualAcc(string $noVa, object $client)
  {
    $jenisVa = isKreditOrTabungan($noVa);
    if (!$jenisVa) return [];
    if ($jenisVa === 'tabungan' and ($client->vaType === 'transfer' || $client->vaType === 'both'))
      return $this->findTabungan($noVa, $client);
    if ($jenisVa === 'kredit'   and ($client->vaType === 'bill'     || $client->vaType === 'both'))
      return $this->findKredit($noVa, $client);
    return [];
  }
  public function findTabungan(string $noVa, object $client)
  {
    $query = <<<SQL
      SELECT trim(nama_nasabah) AS virtualAccountName, status_aktif, trim(no_rekening) as no_rekening
      FROM tabung t JOIN nasabah n USING(nasabah_id)
      WHERE right(trim(t.no_va),:vaLength) = :noVa
    SQL;
    $row = $this->query($query)
      ->bind('noVa', $noVa)
      ->bind('vaLength',  $client->vaLength)
      ->resultSingle();
    $row['paid'] = false;
    $row['type'] = 'transfer';
    $row['totalAmount'] = snapFormatAmount(0, $client->currency);
    return $row;
  }
  public function findKredit(string $noVa, object $client, string $date = null)
  {
    $query = <<<SQL
      SELECT trim(kredit.no_rekening) as no_kredit,kredit.pokok_saldo_akhir as sisa_pokok, TRIM(nasabah.nama_nasabah) as virtualAccountName,
      kredit.bi_jangka_waktu as angs_ke, trim(kredit.no_rek_debet) as no_rekening,kredit.status_aktif,
      kredit.pokok_tunggakan_awal +
          sum(if(floor(kretrans.my_kode_trans/100)=2 AND kretrans.tgl_trans<= :tanggal,kretrans.pokok_trans,0)) -
          sum(if(floor(kretrans.my_kode_trans/100)=3 AND kretrans.VALIDATED=1 AND kretrans.my_kode_trans <> 396 AND kretrans.tgl_trans<= :tanggal,kretrans.pokok_trans,0)) -
          sum(if(floor(kretrans.my_kode_trans/100)=3 AND kretrans.VALIDATED=1 AND kretrans.tgl_trans<= :tanggal,kretrans.disc_pokok,0)) as pokok,
      kredit.bunga_tunggakan_awal - kredit.bunga_disc_awal +
          sum(if(floor(kretrans.my_kode_trans/100)=2 AND kretrans.tgl_trans<= :tanggal,kretrans.bunga_trans,0)) -
          sum(if(floor(kretrans.my_kode_trans/100)=3 AND kretrans.VALIDATED=1 AND kretrans.my_kode_trans <> 396 AND kretrans.tgl_trans<= :tanggal,kretrans.bunga_trans,0)) -
          sum(if(floor(kretrans.my_kode_trans/100)=3 AND kretrans.VALIDATED=1 AND kretrans.tgl_trans<= :tanggal,kretrans.disc_bunga,0)) as bunga,
    if(:includeDenda, kredit.DENDA_TUNGGAKAN_AWAL - kredit.DENDA_DISC_AWAL +
          sum(if(floor(kretrans.my_kode_trans/100)=2 AND kretrans.tgl_trans<= :tanggal,kretrans.DENDA_TRANS,0)) -
          sum(if(floor(kretrans.my_kode_trans/100)=3 AND kretrans.VALIDATED=1 AND kretrans.my_kode_trans <> 396 AND kretrans.tgl_trans<= :tanggal,kretrans.DENDA_TRANS,0)) -
          sum(if(floor(kretrans.my_kode_trans/100)=3 AND kretrans.VALIDATED=1 AND kretrans.tgl_trans<= :tanggal,kretrans.DENDA_TRANS,0)),0) as denda
      FROM kretrans
          INNER JOIN kredit ON kredit.no_rekening = kretrans.no_rekening
          INNER JOIN nasabah ON nasabah.nasabah_id = kredit.nasabah_id
      WHERE RIGHT(TRIM(REPLACE(kredit.no_rekening, '.', '')),:vaLength) = :noVa
    SQL;
    $row = $this->query($query)
      ->bind('noVa', $noVa)
      ->bind('vaLength',  $client->vaLength)
      ->bind('tanggal',  isset($date) ? $date : date('Y-m-d'))
      ->bind('includeDenda', TAGIHAN_DENDA)
      ->resultSingle();
    $pokok = $row['pokok'] < 0 ? 0 : $row['pokok'];
    $bunga = $row['bunga'] < 0 ? 0 : $row['bunga'];
    $denda = $row['denda'] < 0 ? 0 : $row['denda'];

    $total = $pokok + $bunga + $denda;

    $row['paid'] = ($total <= 0);
    $row['type'] = 'bill';
    $row['totalAmount'] = snapFormatAmount($total, $client->currency);
    return $row;
  }
}
