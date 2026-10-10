<?php

namespace App\Http\Controllers;

use App\Support\LocalizedUrl;
use Illuminate\Http\Response;
use XMLWriter;

class SearchDiscoveryController extends Controller
{
    public function sitemap(): Response
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->writePi('xml-stylesheet', 'type="text/css" href="/sitemap.css"');
        $xml->startElementNS(null, 'urlset', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        foreach (['/', '/impressum', '/datenschutz'] as $path) {
            foreach (['de', 'en'] as $locale) {
                $xml->startElement('url');
                $xml->writeElement('loc', LocalizedUrl::to($path, $locale));
                foreach (['de', 'en'] as $alternate) {
                    $xml->startElement('xhtml:link');
                    $xml->writeAttribute('rel', 'alternate');
                    $xml->writeAttribute('hreflang', $alternate);
                    $xml->writeAttribute('href', LocalizedUrl::to($path, $alternate));
                    $xml->endElement();
                }
                $xml->endElement();
            }
        }
        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $sitemap = rtrim(config('app.url'), '/').'/sitemap.xml';

        return response("User-agent: *\nDisallow: /preview\nDisallow: /de/preview\nDisallow: /en/preview\n\nSitemap: {$sitemap}\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
