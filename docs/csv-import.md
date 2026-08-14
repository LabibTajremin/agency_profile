# CSV import and export

**Edulume → Content → Import** takes a CSV and creates or updates content. It is chunked and
resumable, so a 3,000-row catalogue imports on hosting with a 30-second execution limit.

## The format

- UTF-8, comma-separated, first row is the header.
- Column order does not matter. You map columns to fields on screen after upload, and the
  mapping is remembered for next time.
- A row that fails is **reported and skipped**; the run continues. You get a per-row error list
  at the end rather than a single "import failed".
- Re-importing a row whose `slug` already exists updates that item instead of duplicating it.
  This is what makes the export → edit → re-import round trip safe.

A sample file is at [`courses-sample.csv`](samples/courses-sample.csv).

## Course columns

| Column             | Required | Notes                                                    |
| ------------------ | -------- | -------------------------------------------------------- |
| `title`            | yes      | The course name                                          |
| `slug`             | no       | Generated from the title if empty; the update key        |
| `institution`      | yes      | Matched on institution title or slug                     |
| `destination`      | yes      | Matched on destination title or slug                     |
| `study_level`      | yes      | One of the Study Level terms; created if missing         |
| `field_of_study`   | no       | Comma-separated; terms created if missing                |
| `intake`           | no       | Comma-separated, e.g. `September,January`                |
| `duration_months`  | no       | Whole number                                             |
| `tuition_amount`   | no       | Number only — no currency symbol, no thousands separator |
| `tuition_currency` | no       | Three-letter code, e.g. `AUD`                            |
| `ielts`            | no       | Decimal, e.g. `6.5`                                      |
| `description`      | no       | Plain text or basic HTML                                 |
| `content`          | no       | The full body                                            |

## Things that bite

- **Tuition with a currency symbol** (`£12,000`) is rejected with a per-row error. Put the
  number in `tuition_amount` and the code in `tuition_currency`.
- **A spreadsheet's leading zeros** disappear before the file reaches us. Format the column as
  text in Excel or Sheets before saving.
- **A cell starting with `=`, `+`, `-` or `@`** is escaped on export so a spreadsheet does not
  execute it as a formula. That escaping survives a re-import.

## Export

**Edulume → Content → Export** exports the current filter, page by page, and the result
round-trips back through the importer unchanged.
