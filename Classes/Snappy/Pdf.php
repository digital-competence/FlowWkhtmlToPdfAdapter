<?php
namespace DigiComp\FlowWkhtmlToPdfAdapter\Snappy;

use Doctrine\ORM\Mapping as ORM;
use Knp\Snappy\AbstractGenerator;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("prototype")
 */
class Pdf extends \Knp\Snappy\Pdf
{

    /**
     * should Xvfb be used during executeCommand
     * @var bool
     */
    protected $useXvfb = false;

    /**
     * @var \DigiComp\FlowWkhtmlToPdfAdapter\Xvfb\XvfbUtility
     * @Flow\Inject
     */
    protected $xvfbUtility;

    /**
     * @param null  $binary
     * @param array $options
     * @param array $env
     */
    public function __construct($binary = null, $options = array(), $env = array())
    {
        $this->setDefaultExtension('pdf');

        if (is_null($options)) {
            $options = array();
        }
        if (is_null($env)) {
            $env = array();
        }

        AbstractGenerator::__construct($binary, $options, $env);
    }

    /**
     * {@inheritDoc}
     */
    protected function executeCommand($command)
    {
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
