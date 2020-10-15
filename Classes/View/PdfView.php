<?php

namespace DigiComp\FlowWkhtmlToPdfAdapter\View;

use DigiComp\FlowWkhtmlToPdfAdapter\Snappy\Pdf;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Flow\Utility\Environment;
use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Utility\Files;

/*                                                                              *
 * This script belongs to the FLOW3 package "DigiComp.FlowWkhtmlToPdfAdapter".  *
 *                                                                              */

/**
 * PdfView connects up to three templates to generate a PDF using wkhtmltopdf
 */
class PdfView extends AbstractView
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'templateRootPathPattern' => [
            '@packageResourcesPath/Private/Templates',
            'Pattern to be resolved for "@templateRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"',
            'string'
        ],
        'partialRootPathPattern' => [
            '@packageResourcesPath/Private/Partials',
            'Pattern to be resolved for "@partialRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"',
            'string'
        ],
        'layoutRootPathPattern' => [
            '@packageResourcesPath/Private/Layouts',
            'Pattern to be resolved for "@layoutRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"',
            'string'
        ],
        'templateRootPaths' => [
            [],
            'Path(s) to the template root. If NULL, then $this->options["templateRootPathPattern"] will be used to determine the path',
            'array'
        ],
        'partialRootPaths' => [
            [],
            'Path(s) to the partial root. If NULL, then $this->options["partialRootPathPattern"] will be used to determine the path',
            'array'
        ],
        'layoutRootPaths' => [
            [],
            'Path(s) to the layout root. If NULL, then $this->options["layoutRootPathPattern"] will be used to determine the path',
            'array'
        ],
        'bodyTemplatePathAndFilenamePattern' => [
            '@templateRoot/@subpackage/@controller/@action.PDFBody.html',
            'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"',
            'string'
        ],
        'footTemplatePathAndFilenamePattern' => [
            '@templateRoot/@subpackage/@controller/@action.PDFFoot.html',
            'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"',
            'string'
        ],
        'headTemplatePathAndFilenamePattern' => [
            '@templateRoot/@subpackage/@controller/@action.PDFHead.html',
            'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"',
            'string'
        ],
        'partialPathAndFilenamePattern' => [
            '@partialRoot/@subpackage/@partial.@format',
            'Directory pattern for global partials. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@partial", "@format"',
            'string'
        ],
        'layoutPathAndFilenamePattern' => [
            '@layoutRoot/@layout.@format',
            'File pattern for resolving the layout. Following placeholders are supported: "@templateRoot", "@partialRoot", "@layoutRoot", "@subpackage", "@layout", "@format"',
            'string'
        ],
        'templatePathAndFilename' => [
            null,
            'Path and filename of the template file. If set, overrides the templatePathAndFilenamePattern',
            'string'
        ],
        'layoutPathAndFilename' => [
            null,
            'Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern',
            'string'
        ],
        'orientation' => ['portrait', 'Orientation of the page', 'string'],
        'marginLeft' => ['10mm', 'Left margin of the PDF', 'string'],
        'marginTop' => ['10mm', 'Left margin of the PDF', 'string'],
        'marginRight' => ['10mm', 'Left margin of the PDF', 'string'],
        'marginBottom' => ['10mm', 'Left margin of the PDF', 'string'],
        'enableLocalFileAccess' => [false, 'Allow local file access', 'bool'],
        'dpi' => [96, 'Resolution of the PDF', 'int'],
    ];

    /**
     * @var array
     */
    protected $blacklistTemplateOptions = [
        'orientation',
        'marginLeft',
        'marginTop',
        'marginRight',
        'marginBottom',
        'bodyTemplatePathAndFilenamePattern',
        'headTemplatePathAndFilenamePattern',
        'footTemplatePathAndFilenamePattern',
        'enableLocalFileAccess',
        'dpi'
    ];

    /**
     * @var array
     */
    public static $optionsToPdfTranslation = [
        'orientation' => 'orientation',
        'marginLeft' => 'margin-left',
        'marginRight' => 'margin-right',
        'marginTop' => 'margin-top',
        'marginBottom' => 'margin-bottom',
        'enableLocalFileAccess' => 'enable-local-file-access',
        'dpi' => 'dpi',
    ];

    /**
     * @var TemplateView
     */
    protected $headView;

    /**
     * @var TemplateView
     */
    protected $bodyView;

    /**
     * @var TemplateView
     */
    protected $footView;

    /**
     * @var Environment
     * @Flow\Inject
     */
    protected $environment;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $options = [];

        $options['head'] = $this->options;
        $options['foot'] = $this->options;
        $options['body'] = $this->options;

        $options['head']['templatePathAndFilenamePattern'] = $options['head']['headTemplatePathAndFilenamePattern'];
        $options['body']['templatePathAndFilenamePattern'] = $options['body']['bodyTemplatePathAndFilenamePattern'];
        $options['foot']['templatePathAndFilenamePattern'] = $options['foot']['footTemplatePathAndFilenamePattern'];
        foreach ($options as &$partOptions) {
            foreach ($this->blacklistTemplateOptions as $blacklistedOption) {
                unset($partOptions[$blacklistedOption]);
            }
        }

        $this->headView = new TemplateView($options['head']);
        $this->bodyView = new TemplateView($options['body']);
        $this->footView = new TemplateView($options['foot']);
    }

    /**
     * @param ControllerContext $controllerContext
     * @return bool
     */
    public function canRender(ControllerContext $controllerContext)
    {
        return $this->bodyView->canRender($controllerContext);
    }

    /**
     * @return string
     */
    public function render()
    {
        $prefix = uniqid();
        $tmpPath = $this->environment->getPathToTemporaryDirectory();
        $fileName = $tmpPath . $prefix . '.pdf';

        $this->generateFile($fileName);

        $sendFileName = isset($this->variables['pdfFileName']) ? $this->variables['pdfFileName'] : basename($fileName);
        $this->controllerContext->getResponse()->setComponentParameter(
            SetHeaderComponent::class,
            'Content-Type',
            'application/pdf'
        );
        $this->controllerContext->getResponse()->setComponentParameter(
            SetHeaderComponent::class,
            'Content-Disposition',
            sprintf('attachment; filename="%s"', $sendFileName)
        );

        $content = file_get_contents($fileName);
        unlink($fileName);
        return $content;
    }

    /**
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        parent::setControllerContext($controllerContext);
        $this->headView->setControllerContext($controllerContext);
        $this->bodyView->setControllerContext($controllerContext);
        $this->footView->setControllerContext($controllerContext);
    }

    /**
     * @param string $fileName
     */
    protected function generateFile($fileName)
    {
        $this->headView->assignMultiple($this->variables);
        $this->bodyView->assignMultiple($this->variables);
        $this->footView->assignMultiple($this->variables);
        $tmpPath = $this->environment->getPathToTemporaryDirectory() . '/wkhtmltopdf/';
        Files::createDirectoryRecursively($tmpPath);
        @symlink(FLOW_PATH_WEB . '/_Resources', $tmpPath . DIRECTORY_SEPARATOR . '_Resources');

        $pdf = new Pdf();
        $pdf->setTemporaryFolder($tmpPath);

        try {
            $pdf->setOption('header-html', $this->headView->render());
        } catch(InvalidTemplateResourceException $e) {
            $pdf->setOption('header-html', null);
        }

        try {
            $pdf->setOption('footer-html', $this->footView->render());
        } catch(InvalidTemplateResourceException $e) {
            $pdf->setOption('footer-html', null);
        }

        foreach (static::$optionsToPdfTranslation as $source => $target) {
            $pdf->setOption($target, $this->options[$source]);
        }

        $pdf->generateFromHtml($this->bodyView->render(), $fileName);
    }
}
