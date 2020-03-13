<?php

namespace DigiComp\FlowWkhtmlToPdfAdapter\Snappy;

use DigiComp\FlowWkhtmlToPdfAdapter\Xvfb\XvfbUtility;
use Knp\Snappy\AbstractGenerator;
use Neos\Flow\Annotations as Flow;

class Pdf extends \Knp\Snappy\Pdf
{
    /**
     * should Xvfb be used during executeCommand
     * @var bool
     */
    protected $useXvfb = false;

    /**
     * @var XvfbUtility
     * @Flow\Inject
     */
    protected $xvfbUtility;

    /**
     * @param string $binary
     * @param array $options
     * @param array $env
     */
    public function __construct($binary = null, $options = [], $env = [])
    {
        $this->setDefaultExtension('pdf');

        if (is_null($options)) {
            $options = [];
        }
        if (is_null($env)) {
            $env = [];
        }

        AbstractGenerator::__construct($binary, $options, $env);
    }

    /**
     * @inheritDoc
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
