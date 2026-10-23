<?php

namespace App\Traits;

use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;

trait GeneratesQrSvg
{
    private function qrOptions(): QROptions
    {
        return new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'scale'           => 8,
            'outputBase64'    => false,
            'addQuietzone'    => true,
        ]);
    }

    private function renderSvg(string $data): string
    {
        if ($data === '') {
            $data = ' ';
        }
        $qr = new \chillerlan\QRCode\QRCode($this->qrOptions());
        $svg = $qr->render($data);
        return str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $svg);
    }
}
