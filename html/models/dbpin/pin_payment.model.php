<?php

namespace App\models;


class pin_payment extends kuitansi
{
  public function pay(array $trans, object $client)
  {
    $transStatus = false;
    try {
      /* Konfig  */
      $configPin = KONFIG_SETOR_PIN;

      /* Get Data */
      $kuitansi  = $this->getKuitansi($client->modulKuitansi);
      $tabungan = $this->getTabungan($trans['no_rekening']);

      /* Prepare Data */
      $namaNasabah = trim($tabungan['nama_nasabah']);
      $saldoSetelah = $tabungan['saldo_akhir'] + $trans['amount'];

      $transNote = [
        "[{$client->name}] {$trans['serviceName']} IDTRANS: {$trans['uniqueKey']}",
        "[{$configPin['appName']}] Nama:$namaNasabah IDTRANS : {$trans['uniqueKey']}",
        "[{$client->name}] RP. {$trans['amount']}"
      ];

      $transaksi = [
        "tgl_trans" => $trans['date'],
        "no_rekening" => $trans['no_rekening'],
        "kode_trans" =>  $client->kodeTrans,
        "saldo_trans" => $trans['amount'],
        "my_kode_trans" => $configPin['myKodeTrans'],
        "kuitansi" => $kuitansi,
        "userid" => $client->pinUserID,
        "tob" => $configPin['tob'],
        "keterangan" => $transNote[0],
        "flag_cetak" => "N",
        "cab" => $tabungan['cab'],
        "validated" => 1,
        "tgl_input" => $trans['date'],
        "totalkyc" => $trans['amount'],
        "limitkyc" => $tabungan['pendapatan_kyc'],
        "saldoakhir" => $tabungan['saldo_akhir'],
        "saldosetelah" => $saldoSetelah,
        "kode_kas" => $tabungan['kode_kas'],
      ];

      $log = [
        "Tanggal" => $trans['date'],
        "FormName" => $client->name,
        "FormCaption" => $transNote[0],
        "UserId" => $client->pinUserID,
        "UserName" => $configPin['logUsername'],
        "Jam" => date("H:i:s"),
        "no_rekening" => $trans['no_rekening'],
        "warning" => $transNote[1],
        "catatan" => $transNote[2],
        "alamat_comp" => 'Server Bank Gresik',
        "UserApp" => $client->pinUserID,
        "Modul" => $client->modulKuitansi
      ];
      /**  EXECUTING */
      /* Insert transaction */
      $this->insertTransaction($transaksi);
      $transStatus = true;
      /* do the Rest */
      $this->updKuitansi($client->modulKuitansi);
      $this->insert('log', $log);

      $newTabungan = $this->getTabungan($trans['no_rekening']);
      $sms = [
        "nama_nasabah" => trim($newTabungan['nama_nasabah']),
        "no_rekening" => trim($trans['no_rekening']),
        "jmlsetoran" => number_format((float)$trans['amount'], 2, ',', '.'),
        "tgltransaksi" => date_format(date_create($trans['date']), "Y/m/d"),
        "saldosetelah" => number_format((float)$newTabungan['saldo_akhir'], 2, ',', '.'),
        "namalembaga" => (new mysysid())->getKey("nama_lembaga2")
      ];
      (new sms())->sendSMS($sms, $newTabungan['no_hp'], $client->pinUserID);
    } catch (\Exception $e) {
      if (!$transStatus) {
        throw new \Exception("Failed Before Insert Transaction: " . $e->getMessage());
      }
      error_log('Error: ' . $e->getMessage());
    }
    return $transStatus;
  }
  private function getTabungan($rekening)
  {
    $query = <<<SQL
        SELECT saldo_akhir, no_hp, t.cab, t.kode_kas, pendapatan_kyc, nama_nasabah
        FROM tabung t
        JOIN nasabah n ON n.nasabah_id = t.nasabah_id
        WHERE no_rekening = :norekening
    SQL;
    $this->query($query)->bind('norekening', $rekening);
    return $this->resultSingle();
  }

  private function insertTransaction(array $data)
  {

    $this->insert('tabtrans', $data);
    $this->update(
      'tabung',
      [
        'saldo_akhir' => $data['saldosetelah'],
        'status_aktif' => 2,
        'tgl_trans_terakhir' => $data['tgl_trans'],
      ],
      [
        'no_rekening' => $data['no_rekening'],
      ],
    );
    return $this;
  }
}
