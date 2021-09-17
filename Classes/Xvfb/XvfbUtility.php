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
     * Resolution of the virtual frame buffer.
     *
     * @var string
     */
    protected string $resolution = '1024x768x24';

    /**
     * Minimal X display number.
     *
     * @var int
     */
    protected int $minXDisplay = 20;

    /**
     * Maximum X display number.
     *
     * @var int
     */
    protected int $maxXDisplay = 500;

    /**
     * Array of started processes.
     *
     * @var Process[]
     */
    protected array $processes = [];

    /**
     * @return int
     */
    protected function getFreeXDisplay(): int
    {
        // TODO: Well this is really optimistic "free". We could check if it is really free with "xset q".
        return \rand($this->minXDisplay, $this->maxXDisplay);
    }

    /**
     * @return int
     */
    public function startXvfb(): int
    {
        $xDisplay = $this->getFreeXDisplay();

        $process = Process::fromShellCommandline(
            \sprintf(
                'exec /usr/bin/Xvfb -screen 0 %s ' . // -dpi ' . $this->dpi .
                ' -terminate -nolisten tcp :%s' . // could configure font-path for X here with -fp
                ' -tst ',
                $this->resolution,
                $xDisplay
            )
        );
        $process->start();

        // wait for first output
        while (\strlen($process->getErrorOutput()) < 5) {
            \usleep(500);
            // if an error encountered, wait until process dies
            if (\strpos($process->getErrorOutput(), '(EE)') !== false) {
                $process->wait();
            }
        }
        if (!$process->isRunning()) {
            throw new \RuntimeException(
                'X Server could not be started. Error output was: ' . $process->getErrorOutput(),
                1631264336
            );
        }

        $this->processes[$xDisplay] = $process;

        return $xDisplay;
    }

    /**
     * @param int $xDisplay
     */
    public function ensureClosed(int $xDisplay): void
    {
        $process = $this->processes[$xDisplay];
        if ($process->isRunning()) {
            $process->stop();
        }
    }
}
