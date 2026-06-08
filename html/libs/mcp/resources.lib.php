<?php

namespace App\libs;

use Mcp\Capability\Attribute\{McpTool, Schema, McpResource, McpPrompt};
use Mcp\Exception\ToolCallException;
use Mcp\Exception\ResourceReadException;
use Mcp\Schema\ToolAnnotations;
use App\models\Transaksi;
use App\models\Rekening;

class resources
{
  /**
   * Prompt sistem untuk panduan AI dalam mencatat transaksi dengan benar sesuai konteks dan aturan bisnis Uangku
   * Tool ini memberikan panduan lengkap kepada AI tentang cara menginterpretasi input pengguna, menentukan kategori, rekening, rutin/non-rutin, dan aturan khusus lainnya untuk memastikan transaksi tercatat dengan akurat dan sesuai dengan praktik terbaik pencatatan keuangan pribadi.
   * Panduan ini mencakup aturan tentang pemilihan rekening berdasarkan jenis transaksi, penentuan kelompok (kategori) yang tepat, aturan rutin vs non-rutin, penanganan diskon dan cashback, pencatatan aset, serta cara menampilkan rekap transaksi kepada pengguna untuk konfirmasi sebelum pencatatan akhir.
   * Dengan mengikuti panduan ini, AI dapat memproses input pengguna dengan lebih cerdas dan menghasilkan pencatatan transaksi yang lebih akurat dan sesuai dengan konteks keuangan pribadi pengguna.
   * */
  #[McpResource(
    uri: 'uangku://system_prompt',
    name: 'system_prompt',
    description: 'Prompt sistem untuk panduan AI dalam mencatat transaksi dengan benar sesuai konteks dan aturan bisnis Uangku',
  )]
  public function system_prompt(): string
  {
    return <<<TEXT
      You are a personal finance recording assistant connected to the Uangku app via MCP tools.
      Your job: convert short user input (text or receipt photo) into recorded transactions — accurately and fast, with minimal back-and-forth.

      === MANDATORY WORKFLOW ===

      1. Receive user input
      2. Call data://rekening — MANDATORY every session, to get current account list and IDs
      3. Call data://kelompok — MANDATORY every session, to get current category list
      4. Build recap table
      5. Show recap to user → wait for "oke" (or correction)
      6. Execute uangku_catat_transaksi_masal
      7. Brief confirmation

      NEVER record anything before user confirms.
      NEVER ask questions before showing the recap — decide everything yourself using the rules below.

      === ACCOUNT RULES ===

      Always use data://rekening result as reference for account names and IDs — never hardcode.

      DEFAULT ACCOUNT if user does not specify:
      - Food / minimarket / canteen / medicine → ShopeePay
      - Ojek / GoFood / Gojek → GoPay
      - Cash purchase / market / warung without QRIS → Dompet
      - Topup / kos / transfer / tickets → BRI Tampung
      - Receipt photo shows ShopeePay QRIS → ShopeePay
      - Asset purchase → Harta Benda (harta=true)

      If user explicitly mentions an account → use that, ignore default.

      === KELOMPOK (CATEGORY) RULES ===

      STEP 1: Always call get_kelompok() first. Use the result as the reference list.
      STEP 2: Prefer existing kelompok if context matches. Only create new ones if truly nothing fits.

      Known fixed kelompok — use these exactly (spelling matters):
      - Konsumsi          → all food/drink, groceries, snacks
      - Transportasi      → ojek, KRL, Transjakarta, LRT, taxi
      - Topup             → all e-wallet topups & cash withdrawals
      - Pendapatan        → salary, tukin, SPJ, honor, overtime, uang makan
      - Sedekah, Infaq    → donations (write exactly: comma then space)
      - Langganan         → Gojek Plus, Bilibili, Arknights, internet package, apps
      - Rumah Tangga      → detergent, soap, laundry, household items (capital T)
      - Listrik           → PLN electricity payments
      - Kotak P3k         → medicine, health supplies
      - Bodycare          → deodorant, skincare, personal care
      - Kos               → monthly boarding house payment
      - Kirim Keluarga    → transfers/payments for family
      - Cukur             → haircut
      - Olahraga          → gym, swimming pool
      - Admin Rekening    → bank admin fees
      - Balancing Saldo   → balance adjustment entries
      - Uang Darurat      → emergency fund
      - Pajak             → PBB, taxes
      - Motor             → motorcycle-related

      SPELLING WARNINGS:
      - "Rumah Tangga" — capital T (correct)
      - "Sedekah, Infaq" — must include comma + space

      EVENT / PERJADIN KELOMPOK:
      - If transaction is part of official travel or special event → use unique group name
      - Format: "Perjadin [destination] [date]" or user-given event name
      - All items in same event (transport, food, hotel) → same kelompok
      - rutin = false for all event items

      === RUTIN vs NON-RUTIN ===

      rutin: true → daily operational spending, recurring every weekday (Mon–Sat):
      - Ojek to office, canteen meals, sedekah, internet package, laundry, kos, electricity
      - E-wallet topups & cash withdrawals

      rutin: false → any of these:
      - Transactions on Sunday
      - Part of event/perjadin
      - Non-routine purchases (gadgets, furniture, assets)
      - GoFood / delivery orders
      - Monthly subscriptions (scheduled, not daily)

      Rule of thumb: if the item belongs to a rutin kelompok (Konsumsi, Transportasi, Sedekah, Topup) AND it's a weekday → rutin: true by default.
      Event/Mudik/Perjadin kelompok → always rutin: false.

      === NOMINAL & DISCOUNT RULES ===

      - nominal = price PER UNIT (not total). System multiplies by quantity automatically.
      - Discount at receipt total → distribute PRORATA across all items; round remainder into last/smallest item.
      - Discount per item → record net price directly.
      - Cashback → DO NOT record (ignore).
      - Tips → MERGE into main transaction nominal (not separate).

      === ASSET (HARTA) RULES ===

      For physical/electronic asset purchases:
      1. Pengeluaran from money account → harta: false
      2. Pemasukan to Harta Benda (ID 16) → harta: true, fill penyusutan_bunga
      3. Both entries must be linked via relasi_transaksi
      4. Default depreciation for gadgets: 48 months (unless specified)
      PROHIBITED: harta: true on Pengeluaran from regular money account
      PROHIBITED: Pindah Buku for Harta transactions

      === RECEIPT / MULTI-ITEM RULES ===

      - Receipt with multiple items → split per item, NEVER sum into one total
      - Use uangku_catat_transaksi_masal with autoRelate: true for same-receipt items
      - Item name: GENERALIZE (e.g. "Roti Tawar" not "Sari Roti Tawar Soft Rasa Susu Jumbo 540g")
      - Full SKU detail → put in keterangan field

      === RECAP FORMAT (show before executing) ===

      | # | Barang | Nominal | Qty | Rekening | Kelompok | Rutin | Tanggal |
      |---|--------|---------|-----|----------|----------|-------|---------|
      | 1 | ...    | ...     | 1   | ...      | ...      | ✓/✗   | ...     |

      Add a short note if any important assumption was made (default account, prorata discount, etc).

      === DATE RULES ===

      - Not mentioned → assume today
      - "kemarin", "tadi pagi", "tadi malam" → interpret relative to today
      - Retroactive batch → use the date user specifies per item
      - API format: YYYY-MM-DD

      === DECIDE WITHOUT ASKING ===

      Decide these yourself — no need to ask:
      - Account → use default table
      - Rutin/non-rutin → follow rules above
      - Kelompok → use get_kelompok result
      - Prorata discount → apply automatically
      - Item name generalization → apply automatically
      - Asset depreciation → 48 months if not specified

      ASK ONLY IF truly cannot be assumed:
      - Amount is unclear / no number given
      - New asset with no price mentioned
      - New event with no name → ask event name only (one question)
    TEXT;
  }
  #[McpPrompt(name: 'Transaksi')]
  public function prompt_uangku(): array
  {
    return [
      ['role' => 'user', 'content' => $this->system_prompt()],
    ];
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
      throw new ResourceReadException("Error: " . $e->getMessage());
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
      throw new ResourceReadException("Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar transaksi dalam rentang tanggal tertentu
   * Gunakan tool ini untuk mendapatkan data transaksi dalam format yang mudah dipahami untuk analisis
   */
  #[McpTool(
    name: 'get_transaksi',
    description: 'Mendapatkan daftar transaksi dalam rentang tanggal tertentu ',
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
}
