<?php

namespace DigiComp\FlowWkhtmlToPdfAdapter\Snappy;

use DigiComp\FlowWkhtmlToPdfAdapter\Xvfb\XvfbUtility;
use Knp\Snappy\Pdf as KnpSnappyPdf;
use Neos\Flow\Annotations as Flow;

class Pdf extends KnpSnappyPdf
{
    /**
     * If Xvfb should be used during executeCommand.
     *
     * @var bool
     */
    protected bool $useXvfb = false;

    /**
     * @Flow\Inject
     * @var XvfbUtility
     */
    protected $xvfbUtility;

    /**
     * @param string|null $binary
     * @param array|null $options
     * @param array|null $env
     */
    public function __construct(?string $binary = null, ?array $options = [], ?array $env = [])
    {
        $this->setDefaultExtension('pdf');

        if (\is_null($options)) {
            $options = [];
        }
        if (\is_null($env)) {
            $env = [];
        }

        parent::__construct($binary, $options, $env);
    }

    /**
     * @inheritDoc
     */
    protected function executeCommand($command): array
    {
        if ($this->useXvfb) {
            $xDisplay = $this->xvfbUtility->startXvfb();
            $command = 'DISPLAY=:' . $xDisplay . ' ' . $command;
        }

        $result = parent::executeCommand($command);

        if ($this->useXvfb) {
            $this->xvfbUtility->ensureClosed($xDisplay);
        }

        return $result;
    }
}
