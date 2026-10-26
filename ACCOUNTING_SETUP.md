# Accounting module

Open **Accounting & registers** in the sidebar to manage client accounts, income, expenses, allocations, projects/domains, assets, SIMs and reserves. Registers support adding, editing, deleting, searching, CSV export and printing. Change history stores before/after values; version checks prevent overwriting another window's edits.

## Installation

1. Back up the invoice database and deploy the application files.
2. Start MySQL and configure the existing db_config.php connection.
3. Open accounting-setup.php. Create empty accounting tables, or include a local workbook package to import records.
4. Review **Workbook review** before relying on imported expected balances.

accounting_schema.sql creates only new acct_* tables. It does not select another database or alter receipt/payslip records. The normal initializer and database setup also create these tables without importing financial data.

Workbook files and generated data packages are intentionally excluded from Git. Generate them on a trusted machine using Python with openpyxl installed:

    python tools/import-accounting.py "PATH_TO_WORKBOOK.xlsx"

The extractor targets the supplied workbook layout and leaves the source unchanged. It writes the original workbook, manifest, data SQL and combined accounting_install.sql into accounting-private/. Transfer this directory privately when deploying an existing workbook import. Alternatively, select the invoice database in phpMyAdmin and import the generated accounting-private/accounting_install.sql.

Repeat imports preserve app edits to existing source records and recreate deleted source records. Source references must remain intact. Data import is transactional; schema creation is separate because MySQL DDL commits implicitly.

Apache access rules block the private directory, SQL/XLSX downloads and CLI tools. Apply equivalent restrictions when using another web server.

## Calculations and migration review

- Project/domain dates, handlers, departments, priorities, statuses and remarks become editable records. Expiry days use Africa/Accra dates.
- Allocations calculate project amount multiplied by each allocation rate, rounded half up to pesewas. Fixed overrides take precedence, including zero; blank overrides calculate. A variance exposes allocations that do not reconcile to project amounts.
- Expected amounts belong to client/work accounts; installments are separate received transactions. Historical payment formulas become recorded amounts so changing a contract never silently rewrites money already received. Account balances and cash totals calculate live.
- Asset quantities/specifications and SIM identifiers remain editable. Long identifiers are strings; missing leading digits are not invented.
- Reserves retain the workbook's cash deduction semantics without inferring exchange rates from labels.

The default **Original workbook totals** view applies inclusion flags matching original total formulas. **All entries** includes every recorded payment and expense; gross construction receipts and project profit deposits may overlap and require review. New financial records are included by default.

Original populated cells and expanded formulas remain in an immutable source archive, separate from editable registers. Ambiguous dates keep their original text. Unlabelled amounts remain in the archive instead of being invented as transactions. Conflicting expected amounts are preserved for review. Detailed source-specific findings are available in the local manifest and Workbook review page.

The module implements the workbook's business calculations explicitly rather than interpreting arbitrary Excel formulas. Receipt/payslip records remain in their existing workflows and are not automatically counted again as accounting transactions. Enter the corresponding accounting payment/expense for new money; historical records cannot safely be matched by client name alone.

## Responsive layout and verification

Viewport checks covered 320, 390, 640, 768, 820, 1024 and 1440 CSS pixels. Phone registers use labeled cards, forms stack through tablet widths, tabs swipe horizontally, and touch controls have at least 44px targets. Wide summaries scroll within their containers. Inputs use 16px mobile text; bottom navigation reserves safe-area space. Physical devices and all browser engines have not been individually tested.

The extractor independently verifies source formulas with a restricted arithmetic parser and reconciles original totals. Database tests cover counts, source archives, repeat imports, totals, overrides, rounding, identifiers, validation, CRUD, audit history, stale edits and rollback.

    php tools/test-accounting.php ISOLATED_PORT

Run this only against an isolated MariaDB server accessible as root without a password. It uses accounting_test on the explicitly supplied port and requires a locally generated private import package. Never point it at production.
