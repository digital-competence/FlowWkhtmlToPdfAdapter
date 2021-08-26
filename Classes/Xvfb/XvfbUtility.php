<?php

namespace DigiComp\FlowWkhtmlToPdfAdapter\Xvfb;

use Neos\Flow\Annotations as Flow;
use Symfony\Component\Process\Process;

/**
 * @Flow\Scope("singleton")
 */
class XvfbUtility
{
    /**
     * Resoution of the virtual frame buffer
     *
     * @var string
     */
    protected string $resolution = '1024x768x24';

    /**
     * Minimal X Display number
     *
     * @var int
     */
    protected int $minXDisplay = 20;

    /**
     * Maximum X Display number
     *
     * @var int
     */
    protected int $maxXDisplay = 500;

    /**
     * Array of started processes
     *
     * @var array
     */
    protected array $processes = [];

    /**
     * @return int
     */
    protected function getFreeXDisplay(): int
    {
        // TODO: Well this is really optimistic "free" We could check if it is really free with "xset q"
        return rand($this->minXDisplay, $this->maxXDisplay);
    }

    /**
     * @return int
     */
    public function startXvfb(): int
    {
        $xdisplay = $this->getFreeXDisplay();
        $xvfbProcess = sprintf(
            'exec /usr/bin/Xvfb -screen 0 %s ' . // -dpi ' . $this->dpi .
            ' -terminate -nolisten tcp :%s' . // could configure font-path for X here with -fp
            ' -tst ',
            $this->resolution,
            $xdisplay
        );

        $process = Process::fromShellCommandline($xvfbProcess);
        $process->start();
        // Wait for first output
        while (strlen($process->getErrorOutput()) < 5) {
            usleep(500);
            // IF an error encountered wait until process dies
            if (strpos($process->getErrorOutput(), '(EE)') !== false) {
                $process->wait();
            }
        }
        if (! $process->isRunning()) {
            throw new \RuntimeException(
                'X Server could not be started. Error output was: ' . $process->getErrorOutput()
            );
        }

        $this->processes[$xdisplay] = $process;

        return $xdisplay;
    }

    /**
     * @param int $display
     */
    public function ensureClosed(int $display): void
    {
        /* @var Process $process */
        $process = $this->processes[$display];
        if ($process->isRunning()) {
            $process->stop();
        }
    }
}
