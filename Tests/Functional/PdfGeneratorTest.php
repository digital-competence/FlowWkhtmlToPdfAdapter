<?php
namespace DigiComp\FlowWkhtmlToPdfAdapter\Tests\Functional;

use DigiComp\FlowWkhtmlToPdfAdapter\Snappy\Pdf;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the correct CI-Container and the possibility to render pdfs
 */
class PdfGeneratorTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function testPdfCreation() {
        $name = session_save_path() .'/'. uniqid() . '.pdf';
        $pdf = new Pdf();
        file_put_contents($name, $pdf->getOutputFromHtml('<h1>Hello World</h1>'));
        $this->assertFileExists($name);
        unlink($name);
    }
}
