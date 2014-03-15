<?php
namespace DigiComp\FlowWkhtmlToPdfAdapter\Snappy;

use Doctrine\ORM\Mapping as ORM;
use Knp\Snappy\AbstractGenerator;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("prototype")
 */
class Pdf extends \Knp\Snappy\Pdf {

	/**
	 * should Xvfb be used during executeCommand
	 * @var bool
	 */
	protected $useXvfb = FALSE;

	/**
	 * @var \DigiComp\FlowWkhtmlToPdfAdapter\Xvfb\XvfbUtility
	 * @Flow\Inject
	 */
	protected $xvfbUtility;

	public function __construct($binary = null, array $options = array(), array $env = array()) {
		$this->setDefaultExtension('pdf');

		AbstractGenerator::__construct($binary, $options, $env);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function executeCommand($command) {
		$display = '';
		if ($this->useXvfb) {
			$display = $this->xvfbUtility->startXvfb();
			$command = 'DISPLAY=:' . $display . ' ' . $command;
		}

		$returnArray = parent::executeCommand($command);

		if ($this->useXvfb && $display) {
			$this->xvfbUtility->ensureClosed($display);
		}

		return $returnArray;
	}
}
