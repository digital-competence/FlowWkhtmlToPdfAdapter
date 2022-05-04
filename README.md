# DigiComp.FlowWkhtmlToPdfAdapter

This package provides a `PdfView` for your `ActionController`.

## Introduction

The `PdfView` connects up to three Fluid templates:
- `header-html` (optional): The defaultPath is `@templateRoot/@subpackage/@controller/@action.PDFHead.html`
- `body-html` (required): The defaultPath is `@templateRoot/@subpackage/@controller/@action.PDFBody.html`
- `footer-html` (optional): The defaultPath is `@templateRoot/@subpackage/@controller/@action.PDFFoot.html`

`header-html` and `footer-html` will only be used if found.

Additionally, you may set ALL options `Knp\Snappy` (an abstraction of wkthmltopdf) understands in your `Views.yaml` to
configure your `PdfView`:

```yaml
-
  requestFilter: "isFormat('pdf')"
  viewObjectName: "DigiComp\\FlowWkhtmlToPdfAdapter\\View\\PdfView"
  options:
    marginLeft: "0mm"
    marginRight: "0mm"
    marginTop: "0mm"
    marginBottom: "0mm"
```

If you have to use wkhtmltopdf with unpatched Qt you could activate the usage of Xvfb, but really: I would not recommend
that. ;)
