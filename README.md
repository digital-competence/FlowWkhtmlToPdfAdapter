DigiComp.FlowWkhtmlToPdfAdapter
-------------------------------

This Package provides a pdf View for your TYPO3\Flow ActionControllers. 
This view connects up to three Fluid Templates:

- body-html (required, defaultPath: @templateRoot/@subpackage/@controller/@action.PDFBody.html)
- header-html (optional, defaultPath: @templateRoot/@subpackage/@controller/@action.PDFFoot.html)
- footer-html (optional, defaultPath: @templateRoot/@subpackage/@controller/@action.PDFHead.html)
 
Header-html and footer-html will be used, if found.

Additionally you may set ALL options KNP\Snappy (a wkthmltopdf-Abstraction) understands in your Views.yaml 
to configure your PDF View.

	-
      requestFilter: 'isFormat("pdf") && isController("Invoice")'
      viewObjectName: 'DigiComp\FlowWkhtmlToPdfAdapter\View\PdfView'
      options:
        marginLeft: '0mm'
        marginRight: '0mm'
        marginTop: '0mm'
        marginBottom: '0mm'

If you have to use wkhtmltopdf with unpatched Qt you could activate the Xvfb-Usage, but really: I would not recommend
that. ;)