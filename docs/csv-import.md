# CSV import

## Why it is chunked

Shared hosting routinely caps `max_execution_time` at 30 seconds. An importer that cannot stop
and resume simply cannot import a real course catalogue on the hosting most of these sites run
on — and one that dies halfway leaving no cursor is worse than one that never started.

`ImportContentCsv` imports as much as the request's budget allows, then returns a
`CsvImportCursor` recording the next row, the counts so far, and whether the file is finished.
The next request passes that cursor straight back in.

The budget is an injected port, not a reading of the wall clock. The whole point is proving
the importer stops in time, and a test that waits 30 seconds to find out is a test nobody runs.

## Column mapping

Each column of the uploaded file maps to one target:

| Target         | Meaning                                                  |
| -------------- | -------------------------------------------------------- |
| `title`        | The item title. Required — a row without one is reported |
| `content`      | The main body                                            |
| `excerpt`      | The summary                                              |
| `slug`         | The permalink segment                                    |
| `status`       | `publish`, `draft`, …                                    |
| `meta`         | A meta key, given in the mapping                         |
| `taxonomy`     | A taxonomy key; multiple terms separated by `\|`         |
| `relationship` | The other end of a post-to-post relationship             |
| `ignore`       | Nothing; the column is skipped                           |

A `meta`, `taxonomy` or `relationship` column with no key is treated as ignored rather than
half-configured.

## Errors are reported, never fatal

A malformed row is recorded as a `CsvRowError` — row number, column, and what was wrong — and
skipped. One bad row in five thousand must not cost the site owner the other 4,999. The report
comes back with every error from the pass, and the run continues.

## Export

`ExportContentCsv` writes the same column mapping the importer reads, so an export can be
edited in a spreadsheet and imported straight back. Values containing commas, quotes or
newlines are quoted and escaped.

The filters passed to an export are the ones the admin list was showing. An export that
quietly returns everything when the screen said "Postgraduate, Canada" is the kind of surprise
that gets noticed only after it has been mailed to a client.
