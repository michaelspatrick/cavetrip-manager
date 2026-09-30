<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

final class WaiverPdfService
{
    public function render(string $html): string
    {
        $root = dirname(__DIR__, 2);
        $autoload = $root . '/vendor/dompdf/autoload.inc.php';
        if (!is_file($autoload)) {
            throw new \RuntimeException('PDF renderer is not installed. Re-run the v0.18.4 release installer to install Dompdf.');
        }
        require_once $autoload;

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', $root);

        $dompdf = new Dompdf($options);
        $document = '<!doctype html><html><head><meta charset="utf-8"><style>'
            . '@page{margin:0.55in} body{font-family:"DejaVu Sans",sans-serif;font-size:10.5pt;line-height:1.35;color:#111}'
            . 'h1{font-size:18pt;margin:0 0 14pt} h2{font-size:14pt;margin:18pt 0 8pt} h3{font-size:12pt}'
            . 'p{margin:0 0 8pt} ol,ul{margin-top:4pt} .waiver-participants{page-break-before:always}'
            . '.participant-list{margin-bottom:16pt}.signature-block{border-top:1px solid #bbb;padding:10pt 0;page-break-inside:avoid}'
            . '.signature-image{display:block;max-width:320px;max-height:105px;margin:7pt 0;border-bottom:1px solid #777}'
            . '.minor-label{font-weight:bold}.finalization-note{font-size:8.5pt;color:#555;margin-top:18pt}'
            . '</style></head><body>' . $html . '</body></html>';
        $dompdf->loadHtml($document, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}
