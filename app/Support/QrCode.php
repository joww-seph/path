<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    /**
     * An SVG QR code for the given text, using the bacon/bacon-qr-code library Fortify already uses.
     */
    public static function svg(string $text, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        $svg = $writer->writeString($text);

        // Drop the XML declaration so the SVG can be placed inline in HTML.
        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }
}
