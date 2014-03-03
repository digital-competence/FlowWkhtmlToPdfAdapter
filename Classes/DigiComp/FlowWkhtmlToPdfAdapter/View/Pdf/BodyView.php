<?php
namespace DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf;

/*                                                                        *
 * This script belongs to the FLOW3 package "DigiComp.Controls".          *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Class BodyView
 * @package DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf
 * @Flow\Scope("prototype")
 */
class BodyView extends \TYPO3\Fluid\View\TemplateView {

	protected $templateRootPathPattern = '@packageResourcesPath/Private/Templates';

	protected $templatePathAndFilenamePattern = '@templateRoot/@subpackage/@controller/PDFs/@action/Body.html';

	protected $layoutPathAndFilenamePattern = '@layoutRoot/PDFs/@layout.html';

	public function canRender(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		$this->setControllerContext($controllerContext);

		$this->getTemplateSource();
		return TRUE;

	}

}