# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.1.0]
### Added
- Added CreatePdfFromContentExportTrait in order to be able to create and export a pdf with HTML content inside any class.

## [4.0.0]
### Added
- PdfView-Options
    - `headTemplatePathAndFilename` (allows setting `templatePathAndFilename` for `$headView`)
    - `bodyTemplatePathAndFilename` (allows setting `templatePathAndFilename` for `$bodyView`)
    - `footTemplatePathAndFilename` (allows setting `templatePathAndFilename` for `$footView`)

### Removed
- PdfView-Options
    - `templatePathAndFilename` (removed due to unexpected behaviour, replaced with a new options for each view)

## [3.0.0] - 2021-08-21
### Added
- PdfView-Options
    - `download` (if set to true the PdfView will force the browser to download the file instead of displaying it in current tab)
    - `pdfFilename` (a fluid template, to set the filename of your sent pdf file)
    - `pageSize` (passed through to wkhtmltopdf)
    - `disableSmartShrinking` (passed through to wkhtmltopdf)

### Removed
- The PdfView now ignores the special meaning of `filename` variable. If you used it to change the filename, use the provided option now (see `pdfFilename` from above)

### Changed
- PdfView-Options
    - enableLocalFileAccess is now `false` by default
- Require Flow >= 6.3

## [2.3.0] - 2021-06-10
### Added
- wkhtmltopdf option enableLocalFileAccess is now configurable. This adapter will now set this to true by default to guarantee the same behavior as with outdated wkhtmltopdf versions from the past, but it will be false in next major, so change it to `true` now, if you are using local resources
