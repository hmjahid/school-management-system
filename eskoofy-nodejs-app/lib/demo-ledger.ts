/**
 * Demo ledger mirror — eskoofy-laravel-app/database/seeders/DemoLedgerSeeder.php.
 * Exact opening-balance rows reproduced as authored.
 */
export interface DemoLedgerRow {
  note: string;
  debit: number;
  credit: number;
}

export const demoLedgerRows: readonly DemoLedgerRow[] = [
  { note: "Opening balance - Cash on Hand", debit: 150000, credit: 0 },
  { note: "Opening balance - Bank Account", debit: 500000, credit: 0 },
];
