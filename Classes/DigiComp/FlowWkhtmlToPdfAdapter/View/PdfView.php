<?php
namespace DigiComp\FlowWkhtmlToPdfAdapter\View;
use TYPO3\Flow\Annotations as Flow;

/*                                                                        *
 * This script belongs to the FLOW3 package "DigiComp.Controls".          *
 *                                                                        *
 *                                                                        */

class PdfView extends \TYPO3\Flow\Mvc\View\AbstractView {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'bodyTemplateRootPathPattern' => array('@packageResourcesPath/Private/Templates', 'Pattern to be resolved for "@templateRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"', 'string'),
		'bodyPartialRootPathPattern' => array('@packageResourcesPath/Private/Partials', 'Pattern to be resolved for "@partialRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"', 'string'),
		'bodyLayoutRootPathPattern' => array('@packageResourcesPath/Private/Layouts', 'Pattern to be resolved for "@layoutRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"', 'string'),

		'bodyTemplateRootPaths' => array(NULL, 'Path(s) to the template root. If NULL, then $this->options["templateRootPathPattern"] will be used to determine the path', 'array'),
		'bodyPartialRootPaths' => array(NULL, 'Path(s) to the partial root. If NULL, then $this->options["partialRootPathPattern"] will be used to determine the path', 'array'),
		'bodyLayoutRootPaths' => array(NULL, 'Path(s) to the layout root. If NULL, then $this->options["layoutRootPathPattern"] will be used to determine the path', 'array'),

		'bodyTemplatePathAndFilenamePattern' => array('@templateRoot/@subpackage/@controller/@action.@format', 'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"', 'string'),
		'bodyPartialPathAndFilenamePattern' => array('@partialRoot/@subpackage/@partial.@format', 'Directory pattern for global partials. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@partial", "@format"', 'string'),
		'bodyLayoutPathAndFilenamePattern' => array('@layoutRoot/@layout.@format', 'File pattern for resolving the layout. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@layout", "@format"', 'string'),

		'bodyTemplatePathAndFilename' => array(NULL, 'Path and filename of the template file. If set,  overrides the templatePathAndFilenamePattern', 'string'),
		'bodyLayoutPathAndFilename' => array(NULL, 'Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern', 'string'),

	);

	/**
	 * Path to wkhtmltopdf binary
	 * @var string
	 */
	protected $binPath = '/usr/bin/wkhtmltopdf';

	/**
	 * @var \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\HeadView
	 */
	protected $headView;

	/**
	 * @var \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\BodyView
	 */
	protected $bodyView;

	/**
	 * @var \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\FootView
	 */
	protected $footView;

	/**
	 * Set to true, if you want to keep the temporary html files, generated during rendering
	 * (useful for debugging)
	 * @var boolean
	 */
	protected $keepTmpFiles = TRUE;

	/**
	 * Which orientation should the PDF have? landscape | portrait
	 * @var string
	 */
	protected $orientation = 'portrait';

	/**
	 * Page margin top
	 * @var integer
	 */
	protected $marginTop;

	/**
	 * Page margin right
	 * @var integer
	 */
	protected $marginRight;

	/**
	 * Page margin bottom
	 * @var integer
	 */
	protected $marginBottom;

	/**
	 * Page margin left
	 * @var integer
	 */
	protected $marginLeft;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $tmpFiles;

	/**
	 * @var boolean
	 */
	protected $disableSmartShrink = TRUE;

	protected $settings;

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 * @Flow\Inject
	 */
	protected $environment;

    protected $useXvfb = TRUE;

    protected $dpi = 96;

	public function __construct() {
		#var_dump($this->supportedOptions);
		#die();
		/*$this->headView = new \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\HeadView($options);
		$this->bodyView = new \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\BodyView($options);
		$this->footView = new \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\FootView($options);*/

		$this->headView = new \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\HeadView();
		$this->bodyView = new \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\BodyView();
		$this->footView = new \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\FootView();

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



	/**
	 * Writes the PDF to target
	 *
	 * @throws \DigiComp\FlowWkhtmlToPdfAdapter\View\RenderException if the PDF could not be created (internal error or Service unavaible: ServiceUnavailableException)
	 * @return string filename of rendered pdf
	 */
	public function write() {
		$prefix = uniqid();
		$tmpPath = $this->environment->getPathToTemporaryDirectory();
		$fileName = $tmpPath . $prefix . '.pdf';

		#t3lib_div::devLog('Rendering PDF', 'PdfGenerator', 1, $this->variables);
		foreach ($this->variables as $key => $val) {
			$this->headView->assign($key, $val);
			$this->bodyView->assign($key, $val);
			$this->footView->assign($key, $val);
		}

		$arguments = array();

		if ($this->headView->canRender($this->controllerContext)) {
			$this->tmpFiles[] = $tmpPath . $prefix . '_head.html';
			file_put_contents($$tmpPath . $prefix . '_head.html', $this->headView->render());
			$arguments[] = sprintf('--header-html %s_head.html', $tmpPath . $prefix);
		}

		if ($this->footView->canRender($this->controllerContext)) {
			$this->tmpFiles[] = $tmpPath . $prefix . '_footer.html';
			file_put_contents($tmpPath . $prefix . '_footer.html', $this->footView->render());
			$arguments[] = sprintf('--footer-html %s_footer.html', $tmpPath . $prefix);
		}

		$this->tmpFiles[] = $tmpPath . $prefix . '_body.html';
		file_put_contents($tmpPath . $prefix . '_body.html', $this->bodyView->render());
		$arguments = array_merge($arguments, array(
			sprintf('--margin-top %smm', $this->marginTop),
			sprintf('--margin-right %smm', $this->marginRight),
			sprintf('--margin-bottom %smm', $this->marginBottom),
			sprintf('--margin-left %smm', $this->marginLeft),
			#"--image-quality 100",
			"--dpi 300",
			#"--image-dpi 96",
			$this->disableSmartShrink ? "--disable-smart-shrinking" : '',
			"--orientation " . $this->orientation,
			$tmpPath . $prefix . '_body.html',
			$fileName
		));
		$returnVar = 0;
		$result = array();

        if ($this->useXvfb) {
            $xdisplay = rand(10, 500);
            $xcmd = '/usr/bin/Xvfb -screen 0 1024x768x24 './/-dpi ' . $this->dpi .
                ' -terminate -nolisten tcp :' . $xdisplay . //could configure font-path for X here with -fp
                ' -tst' .
                ' 2> /dev/null &';

            $xfvb = popen($xcmd, 'r');
            exec(sprintf('DISPLAY=:%s ', $xdisplay) . $this->binPath . ' ' . join(" ", $arguments) . ' 2>&1', $result, $returnVar);
            pclose($xfvb);
        } else {
            exec($this->binPath . ' ' . join(" ", $arguments) . ' 2>&1', $result, $returnVar);
        }

		if (! $this->keepTmpFiles) {
			foreach($this->tmpFiles as $file) {
				unlink($file);
			}
		}
		if ($returnVar && !file_exists($fileName))
			throw new RenderException('wkhtmltopdf could not render your HTML. Command: '
				. $this->binPath . ' ' . join(" ", $arguments) . ' Code: '. $returnVar .' Result: '
				. join(" ", $result));
		return $fileName;
	}

	public function render() {
		$fileName = $this->write();
		$this->controllerContext->getResponse()->setHeader('Content-Type', 'application/pdf', TRUE);
		$this->controllerContext->getResponse()->setHeader('Content-Disposition', sprintf('attachment; filename="%s"', basename($fileName)));
		$this->controllerContext->getResponse()->send();
		readfile($fileName);
		if (! $this->keepTmpFiles) {
			unlink($fileName);
		}
		return '';
	}

	/**
	 * @param \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\BodyView $bodyView
	 */
	public function setBodyView($bodyView) {
		$this->bodyView = $bodyView;
	}

	/**
	 * @return \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\BodyView
	 */
	public function getBodyView() {
		return $this->bodyView;
	}

	/**
	 * @param \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\HeadView $headView
	 */
	public function setHeadView($headView) {
		$this->headView = $headView;
	}

	/**
	 * @return \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\HeadView
	 */
	public function getHeadView() {
		return $this->headView;
	}

	/**
	 * @param \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\FootView $footView
	 */
	public function setFootView($footView) {
		$this->footView = $footView;
	}

	/**
	 * @return \DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf\FootView
	 */
	public function getFootView() {
		return $this->footView;
	}

	public function setControllerContext(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		parent::setControllerContext($controllerContext);
		$this->headView->setControllerContext($controllerContext);
		$this->bodyView->setControllerContext($controllerContext);
		$this->footView->setControllerContext($controllerContext);
	}


}