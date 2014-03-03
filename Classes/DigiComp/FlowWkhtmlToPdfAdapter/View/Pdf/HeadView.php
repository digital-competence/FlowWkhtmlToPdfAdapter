<?php
namespace DigiComp\FlowWkhtmlToPdfAdapter\View\Pdf;

/*                                                                        *
 * This script belongs to the FLOW3 package "DigiComp.Controls".          *
 *                                                                        *
 *                                                                        */

class HeadView extends \TYPO3\Fluid\View\TemplateView {

	protected $templateRootPathPattern = '@packageResourcesPath/Private/Templates';

	protected $templatePathAndFilenamePattern = '@templateRoot/@subpackage/@controller/PDFs/@action/Head.html';

	protected $layoutPathAndFilenamePattern = '@layoutRoot/PDFs/@layout.html';

}