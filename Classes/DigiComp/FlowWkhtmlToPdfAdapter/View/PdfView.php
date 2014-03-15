<?php
namespace DigiComp\FlowWkhtmlToPdfAdapter\View;
use DigiComp\FlowWkhtmlToPdfAdapter\Snappy\Pdf;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\View\TemplateView;

/*                                                                              *
 * This script belongs to the FLOW3 package "DigiComp.FlowWkhtmlToPdfAdapter".  *
 *                                                                              */
class PdfView extends \TYPO3\Flow\Mvc\View\AbstractView {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'templateRootPathPattern' => array('@packageResourcesPath/Private/Templates', 'Pattern to be resolved for "@templateRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"', 'string'),
		'partialRootPathPattern' => array('@packageResourcesPath/Private/Partials', 'Pattern to be resolved for "@partialRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"', 'string'),
		'layoutRootPathPattern' => array('@packageResourcesPath/Private/Layouts', 'Pattern to be resolved for "@layoutRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"', 'string'),

		'templateRootPaths' => array(NULL, 'Path(s) to the template root. If NULL, then $this->options["templateRootPathPattern"] will be used to determine the path', 'array'),
		'partialRootPaths' => array(NULL, 'Path(s) to the partial root. If NULL, then $this->options["partialRootPathPattern"] will be used to determine the path', 'array'),
		'layoutRootPaths' => array(NULL, 'Path(s) to the layout root. If NULL, then $this->options["layoutRootPathPattern"] will be used to determine the path', 'array'),

		'bodyTemplatePathAndFilenamePattern' => array('@templateRoot/@subpackage/@controller/@action.PDFBody.html', 'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"', 'string'),
		'footTemplatePathAndFilenamePattern' => array('@templateRoot/@subpackage/@controller/@action.PDFFoot.html', 'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"', 'string'),
		'headTemplatePathAndFilenamePattern' => array('@templateRoot/@subpackage/@controller/@action.PDFHead.html', 'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"', 'string'),
		'partialPathAndFilenamePattern' => array('@partialRoot/@subpackage/@partial.@format', 'Directory pattern for global partials. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@partial", "@format"', 'string'),
		'layoutPathAndFilenamePattern' => array('@layoutRoot/@layout.@format', 'File pattern for resolving the layout. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@layout", "@format"', 'string'),

		'templatePathAndFilename' => array(NULL, 'Path and filename of the template file. If set,  overrides the templatePathAndFilenamePattern', 'string'),
		'layoutPathAndFilename' => array(NULL, 'Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern', 'string'),

		'orientation' => array('portrait', 'Orientation of the page', 'string'),
		'marginLeft' => array('10mm', 'Left margin of the PDF', 'string'),
		'marginTop' => array('10mm', 'Left margin of the PDF', 'string'),
		'marginRight' => array('10mm', 'Left margin of the PDF', 'string'),
		'marginBottom' => array('10mm', 'Left margin of the PDF', 'string'),
		'dpi' => array(96, 'Resolution of the PDF', 'int'),
	);

	protected $blacklistTemplateOptions = array(
		'orientation', 'marginLeft', 'marginTop', 'marginRight', 'marginBottom',
		'bodyTemplatePathAndFilenamePattern', 'headTemplatePathAndFilenamePattern',
		'footTemplatePathAndFilenamePattern', 'dpi'
	);

	protected $optionsToPdfTranslation = array(
		'orientation' => 'orientation',
		'marginLeft' => 'margin-left',
		'marginRight' => 'margin-right',
		'marginTop' => 'margin-top',
		'marginBottom' => 'margin-bottom',
		'dpi' => 'dpi',
	);

	/**
	 * @var \TYPO3\Fluid\View\TemplateView
	 */
	protected $headView;

	/**
	 * @var \TYPO3\Fluid\View\TemplateView
	 */
	protected $bodyView;

	/**
	 * @var \TYPO3\Fluid\View\TemplateView
	 */
	protected $footView;

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 * @Flow\Inject
	 */
	protected $environment;

	public function __construct(array $options = array()) {
		parent::__construct($options);

		$options = array();

		$options['head'] = $this->options;
		$options['foot'] = $this->options;
		$options['body'] = $this->options;

		$options['head']['templatePathAndFilenamePattern'] = $options['head']['headTemplatePathAndFilenamePattern'];
		$options['body']['templatePathAndFilenamePattern'] = $options['body']['bodyTemplatePathAndFilenamePattern'];
		$options['foot']['templatePathAndFilenamePattern'] = $options['foot']['footTemplatePathAndFilenamePattern'];
		foreach($options as &$partOptions) {
			foreach($this->blacklistTemplateOptions as $blacklistedOption) {
				unset($partOptions[$blacklistedOption]);
			}
		}

		$this->headView = new TemplateView($options['head']);
		$this->bodyView = new TemplateView($options['body']);
		$this->footView = new TemplateView($options['foot']);
	}

	/**
	 * Inject TypoScript Settings
	 *
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	public function canRender(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		return $this->bodyView->canRender($controllerContext);
	}

	public function render() {
		$prefix = uniqid();
		$tmpPath = $this->environment->getPathToTemporaryDirectory();
		$fileName = $tmpPath . $prefix . '.pdf';

		$this->headView->assignMultiple($this->variables);
		$this->bodyView->assignMultiple($this->variables);
		$this->footView->assignMultiple($this->variables);

		$pdf = new Pdf;

		if ($this->headView->canRender($this->controllerContext)) {
			$pdf->setOption('header-html', $this->headView->render());
		}
		if ($this->footView->canRender($this->controllerContext)) {
			$pdf->setOption('footer-html', $this->footView->render());
		}

		foreach($this->optionsToPdfTranslation as $source => $target) {
			$pdf->setOption($target, $this->options[$source]);
		}

		$pdf->generateFromHtml($this->bodyView->render(), $fileName);

		$sendFileName = isset($this->variables['pdfFileName']) ? $this->variables['pdfFileName'] : basename($fileName);
		$this->controllerContext->getResponse()->setHeader('Content-Type', 'application/pdf', TRUE);
		$this->controllerContext->getResponse()->setHeader('Content-Disposition', sprintf('attachment; filename="%s"', $sendFileName));
		$this->controllerContext->getResponse()->send();
		readfile($fileName);
		unlink($fileName);
		return '';
	}

	public function setControllerContext(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		parent::setControllerContext($controllerContext);
		$this->headView->setControllerContext($controllerContext);
		$this->bodyView->setControllerContext($controllerContext);
		$this->footView->setControllerContext($controllerContext);
	}

}