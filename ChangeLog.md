# CHANGELOG
All notable changes to this project will be documented in this file.

Since 2.3 the format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Unreleased

### Added
- PdfView-Options
  - `download` (if set to true the PdfView will force the browser to download the file instead of displaying it in current tab)
  - `pdfFilename` (a fluid template, to set the filename of your sent pdf file)
  - `pageSize` (passed through to wkhtmltopdf)
  - `disableSmartShrinking` (passed through to wkhtmltopdf)
    
### Removed
- The PdfView now ignores the special meaning of `filename` variable. If you used it to change the sent filename, use the provided option now (see `pdfFilename` from above)

### Changed
- PdfView-Options
  - enableLocalFileAccess is now `false` by default

## 2.3.0 2021-06-10

### Added
- wkhtmltopdf option enableLocalFileAccess is now configurable. This adapter will now set this to true by default to guarantee the same behavior as with outdated wkhtmltopdf versions from the past, but it will be false in next major, so change it to `true` now, if you are using local resources
