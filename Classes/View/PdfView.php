<?php

namespace DigiComp\FlowWkhtmlToPdfAdapter\View;

use DigiComp\FlowWkhtmlToPdfAdapter\Snappy\Pdf;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Exception as NeosFlowMvcException;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Flow\Utility\Environment;
use Neos\Flow\Utility\Exception as NeosFlowUtilityException;
use Neos\FluidAdaptor\Exception as NeosFluidAdaptorException;
use Neos\FluidAdaptor\View\AbstractTemplateView;
use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;
use Neos\FluidAdaptor\View\StandaloneView;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;

/**
 * PdfView connects up to three templates to generate a PDF using wkhtmltopdf.
 */
class PdfView extends AbstractView
{
    /**
     * @inheritDoc
     */
    protected $supportedOptions = [
        'templateRootPathPattern' => [
            '@packageResourcesPath/Private/Templates',
            'Pattern to be resolved for "@templateRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"',
            'string',
        ],
        'partialRootPathPattern' => [
            '@packageResourcesPath/Private/Partials',
            'Pattern to be resolved for "@partialRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"',
            'string',
        ],
        'layoutRootPathPattern' => [
            '@packageResourcesPath/Private/Layouts',
            'Pattern to be resolved for "@layoutRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"',
            'string',
        ],
        'templateRootPaths' => [
            [],
            'Path(s) to the template root. If NULL, then $this->options["templateRootPathPattern"] will be used to determine the path',
            'array',
        ],
        'partialRootPaths' => [
            [],
            'Path(s) to the partial root. If NULL, then $this->options["partialRootPathPattern"] will be used to determine the path',
            'array',
        ],
        'layoutRootPaths' => [
            [],
            'Path(s) to the layout root. If NULL, then $this->options["layoutRootPathPattern"] will be used to determine the path',
            'array',
        ],
        'headTemplatePathAndFilenamePattern' => [
            '@templateRoot/@subpackage/@controller/@action.PDFHead.html',
            'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"',
            'string',
        ],
        'bodyTemplatePathAndFilenamePattern' => [
            '@templateRoot/@subpackage/@controller/@action.PDFBody.html',
            'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"',
            'string',
        ],
        'footTemplatePathAndFilenamePattern' => [
            '@templateRoot/@subpackage/@controller/@action.PDFFoot.html',
            'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"',
            'string',
        ],
        'partialPathAndFilenamePattern' => [
            '@partialRoot/@subpackage/@partial.@format',
            'Directory pattern for global partials. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@partial", "@format"',
            'string',
        ],
        'layoutPathAndFilenamePattern' => [
            '@layoutRoot/@layout.@format',
            'File pattern for resolving the layout. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@layout", "@format"',
            'string',
        ],
        'headTemplatePathAndFilename' => [
            null,
            'Path and filename of the template file. If set, overrides the headTemplatePathAndFilenamePattern',
            'string',
        ],
        'bodyTemplatePathAndFilename' => [
            null,
            'Path and filename of the template file. If set, overrides the bodyTemplatePathAndFilenamePattern',
            'string',
        ],
        'footTemplatePathAndFilename' => [
            null,
            'Path and filename of the template file. If set, overrides the footTemplatePathAndFilenamePattern',
            'string',
        ],
        'layoutPathAndFilename' => [
            null,
            'Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern',
            'string',
        ],
        'orientation' => ['portrait', 'Orientation of the page.', 'string'],
        'marginLeft' => ['10mm', 'Left margin of the PDF.', 'string'],
        'marginTop' => ['10mm', 'Top margin of the PDF.', 'string'],
        'marginRight' => ['10mm', 'Right margin of the PDF.', 'string'],
        'marginBottom' => ['10mm', 'Bottom margin of the PDF.', 'string'],
        'enableLocalFileAccess' => [false, 'Allow local file access.', 'bool'],
        'disableSmartShrinking' => [false, 'Disable smart-shrinking.', 'bool'],
        'pageSize' => ['A4', 'Page size.', 'string'],
        'dpi' => [96, 'Resolution of the PDF.', 'int'],
        'download' => [true, 'Force browser to download.', 'bool'],
        'pdfFilename' => ['{controller}-{action}.pdf', 'Name of the PDF file to download.', 'string'],
    ];

    /**
     * @var array
     */
    protected array $blacklistTemplateOptions = [
        'headTemplatePathAndFilenamePattern',
        'bodyTemplatePathAndFilenamePattern',
        'footTemplatePathAndFilenamePattern',
        'headTemplatePathAndFilename',
        'bodyTemplatePathAndFilename',
        'footTemplatePathAndFilename',
        'orientation',
        'marginLeft',
        'marginTop',
        'marginRight',
        'marginBottom',
        'enableLocalFileAccess',
        'disableSmartShrinking',
        'pageSize',
        'dpi',
        'download',
        'pdfFilename',
    ];

    /**
     * @var array
     */
    public static array $optionsToPdfTranslation = [
        'orientation' => 'orientation',
        'marginLeft' => 'margin-left',
        'marginTop' => 'margin-top',
        'marginRight' => 'margin-right',
        'marginBottom' => 'margin-bottom',
        'enableLocalFileAccess' => 'enable-local-file-access',
        'disableSmartShrinking' => 'disable-smart-shrinking',
        'pageSize' => 'page-size',
        'dpi' => 'dpi',
    ];

    /**
     * @var AbstractTemplateView
     */
    protected AbstractTemplateView $headView;

    /**
     * @var AbstractTemplateView
     */
    protected AbstractTemplateView $bodyView;

    /**
     * @var AbstractTemplateView
     */
    protected AbstractTemplateView $footView;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @param array $options
     * @throws NeosFlowMvcException
     * @throws NeosFluidAdaptorException
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $templateOptions = $this->options;
        foreach ($this->blacklistTemplateOptions as $blacklistTemplateOption) {
            unset($templateOptions[$blacklistTemplateOption]);
        }

        $templateOptionsForPart = [];

        foreach (['head', 'body', 'foot'] as $part) {
            $partTemplateOptions = $templateOptions;
            foreach (['templatePathAndFilenamePattern', 'templatePathAndFilename'] as $templatePathAndFilenameOption) {
                $partTemplateOptions[$templatePathAndFilenameOption] =
                    $this->options[$part . \ucfirst($templatePathAndFilenameOption)];
            }

            $templateOptionsForPart[$part] = $partTemplateOptions;
        }

        $this->headView = new TemplateView($templateOptionsForPart['head']);
        $this->bodyView = new TemplateView($templateOptionsForPart['body']);
        $this->footView = new TemplateView($templateOptionsForPart['foot']);
    }

    /**
     * @return false|string
     * @throws FilesException
     * @throws NeosFlowUtilityException
     */
    public function render()
    {
        $filename = $this->environment->getPathToTemporaryDirectory() . \uniqid() . '.pdf';

        $this->generateFile($filename);

        $this->controllerContext->getResponse()->setContentType('application/pdf');

        if ($this->options['download']) {
            $filenameTemplate = new StandaloneView();
            $filenameTemplate->setTemplateSource($this->options['pdfFilename']);
            $filenameTemplate->assignMultiple($this->variables);
            $filenameTemplate->assignMultiple(
                [
                    'controller' => $this->controllerContext->getRequest()->getControllerName(),
                    'action' => $this->controllerContext->getRequest()->getControllerActionName(),
                ]
            );

            $this->controllerContext->getResponse()->setComponentParameter(
                SetHeaderComponent::class,
                'Content-Disposition',
                'attachment; filename="' . $filenameTemplate->render() . '"'
            );
        }

        $content = \file_get_contents($filename);

        \unlink($filename);

        return $content;
    }

    /**
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext): void
    {
        parent::setControllerContext($controllerContext);

        $this->headView->setControllerContext($controllerContext);
        $this->bodyView->setControllerContext($controllerContext);
        $this->footView->setControllerContext($controllerContext);
    }

    /**
     * @param string $filename
     * @throws FilesException
     * @throws NeosFlowUtilityException
     */
    protected function generateFile(string $filename): void
    {
        $this->headView->assignMultiple($this->variables);
        $this->bodyView->assignMultiple($this->variables);
        $this->footView->assignMultiple($this->variables);

        $tmpPath = $this->environment->getPathToTemporaryDirectory() . '/wkhtmltopdf/';
        Files::createDirectoryRecursively($tmpPath);
        Files::createRelativeSymlink(\FLOW_PATH_WEB . '_Resources', $tmpPath . \DIRECTORY_SEPARATOR . '_Resources');

        $pdf = new Pdf();
        $pdf->setTemporaryFolder($tmpPath);

        foreach (static::$optionsToPdfTranslation as $option => $pdfTranslation) {
            $pdf->setOption($pdfTranslation, $this->options[$option]);
        }

        try {
            $headerHtml = $this->headView->render();
        } catch (InvalidTemplateResourceException $exception) {
            // header is optional, so invalid template is ok
        }
        $pdf->setOption('header-html', $headerHtml ?? null);

        try {
            $footerHtml = $this->footView->render();
        } catch (InvalidTemplateResourceException $exception) {
            // footer is optional, so invalid template is ok
        }
        $pdf->setOption('footer-html', $footerHtml ?? null);

        $pdf->generateFromHtml($this->bodyView->render(), $filename);
    }
}
