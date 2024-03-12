<?php

declare(strict_types=1);

namespace DigiComp\FlowWkhtmlToPdfAdapter\Traits;

use DigiComp\FlowWkhtmlToPdfAdapter\Snappy\Pdf;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;

trait CreatePdfFromContentExportTrait
{
    /**
     * @Neos\Flow\Annotations\Inject
     * @var \Neos\Flow\Utility\Environment
     */
    protected $environment;

    /**
     * @var array
     */
    protected array $wkhtmlToPdfOptions = [];

    /**
     * @param array $wkhtmlToPdfOptions
     */
    public function setWkhtmlToPdfOptions(array $wkhtmlToPdfOptions): void
    {
        $this->wkhtmlToPdfOptions = $wkhtmlToPdfOptions;
    }

    /**
     * @param string $content
     * @param string $prefixForTmpFolder
     * @return false|string
     * @throws FilesException
     */
    protected function createPdfFromContent(string $content, string $prefixForTmpFolder = 'PdfDocument'): string
    {
        $temporaryDirectory = Files::concatenatePaths([
            $this->environment->getPathToTemporaryDirectory(),
            \uniqid($prefixForTmpFolder . '.', true),
        ]);
        try {
            Files::createDirectoryRecursively($temporaryDirectory);
            if (
                !\symlink(
                    Files::concatenatePaths([\FLOW_PATH_WEB, '_Resources']),
                    Files::concatenatePaths([$temporaryDirectory, '_Resources']),
                )
            ) {
                throw new \RuntimeException('Could not create symlink to Web/_Resources/', 1705522822);
            }
            $pdf = new Pdf();
            $pdf->setTemporaryFolder($temporaryDirectory);
            $tmpName = $temporaryDirectory . \DIRECTORY_SEPARATOR . 'document.pdf';
            $pdf->setOptions($this->wkhtmlToPdfOptions);
            $pdf->generateFromHtml($content, $tmpName);
            return \file_get_contents($tmpName);
        } finally {
            Files::removeDirectoryRecursively($temporaryDirectory);
        }
    }
}
