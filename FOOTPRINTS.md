# Data footprints

Open **Overview → Data footprints** to see which signed-in email created, edited, recorded a payment against, or deleted a record. Filter by email, register and record ID. Each event keeps its timestamp and the before/after data. The page uses record cards on phones and a paginated table on larger screens.

Receipt and payslip detail pages show the original entry email and link to the record's history. Accounting edit screens show the same information. Editing another user's record does not overwrite its original creator. A workbook import through Accounting setup records the importing user's email as a separate import event; it does not claim that user originally authored historical workbook rows.

## Deployment

The additive `app_activity` table is created automatically on database connection after pulling the update. The database user needs CREATE permission. If schema changes are managed separately, import `activity_schema.sql` into the existing invoice database. The full accounting schema and locally generated accounting installation SQL also include it.

No existing business records or prior accounting audit entries are rewritten. Historical/imported records without a creation footprint display **Not recorded (historical/imported)**. Their original authors cannot be recovered automatically. CLI changes have a null actor and display **System / import**. Direct SQL edits/imports outside the application do not produce app footprints.

## Integrity

The actor email is read on the server from the validated session. Submitted actor/creator email fields are ignored. Each normal business mutation and its footprint share one database transaction. If recording the footprint fails, the receipt, payslip or accounting change rolls back too. Receipt/payslip updates that change no data do not create redundant events. Payment reads are locked in the same transaction as the update and footprint.

Footprint times use UTC database connections, equivalent to Africa/Accra. Audit history is available only to signed-in approved users, matching the application's existing access model. There is no UI or API to edit or delete footprint rows. This is application audit history, not cryptographic protection against a database administrator.

## Validation

The isolated footprint suite passed 93 integration checks covering receipts, payslips, accounting records, distinct creator/editor accounts, spoofed submitted emails, payment behavior, before/after values, deleted-record history, unchanged and missing records, creator display, filtering, workbook-import attribution and forced audit-failure rollback (including import data). The accounting suite passed 66 checks and authentication passed 69 HTTP checks. Responsive footprint views were checked at 320, 390, 820 and 1440 CSS pixels.

Run `tools/test-footprints.py PHP_EXECUTABLE` only against its disposable localhost Apache/MariaDB fixture, as described in the script. The fixture also needs the synthetic import package used by its import checks. It never targets production data.
