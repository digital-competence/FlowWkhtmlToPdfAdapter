<?php

declare(strict_types=1);

namespace DigiComp\FlowWkhtmlToPdfAdapter\Tests\Functional;

use DigiComp\FlowWkhtmlToPdfAdapter\Snappy\Pdf;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the correct CI-Container and the possibility to render pdfs.
 */
class PdfGeneratorTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function testPdfCreation(): void
    {
        $name = \session_save_path() . '/' . \uniqid() . '.pdf';

        \file_put_contents($name, (new Pdf())->getOutputFromHtml('<h1>Hello World</h1>'));
        self::assertFileExists($name);

        \unlink($name);
    }
}
