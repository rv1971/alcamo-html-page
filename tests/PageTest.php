<?php

namespace alcamo\html_page;

use Laminas\Diactoros\Stream;
use PHPUnit\Framework\TestCase;
use alcamo\html_creation\element\P;
use alcamo\rdfa\RdfaData;

class PageTest extends TestCase
{
    public function testBasics()
    {
        $rdfaData = RdfaData::newFromIterable(
            [
                [ 'dc:format', 'application/xhtml+xml' ],
                  [ 'dc:title', 'Lorem ipsum' ]
            ]
        );

        $factory = new Factory($rdfaData);

        $page = new Page($factory);

        $this->assertSame($factory, $page->getHtmlFactory());

        $this->assertInstanceof(Stream::class, $page->getBody());

        $this->assertSame(200, $page->getStatusCode());

        $page->setStatusCode(404);

        $this->assertSame(404, $page->getStatusCode());

        $page->begin();

        $bodyText = new P(
            'At vero eos et accusam et justo duo dolores et ea rebum. Stet '
            . 'clita kasd gubergren, no sea takimata sanctus est Lorem ipsum '
            . 'dolor sit amet.'
        );

        $page->write($bodyText);

        $page->end(true);

        $expectedText = $factory['page']->createBegin()
            . $bodyText
            . $factory['page']->createEnd();

        $this->assertSame(
            preg_replace('/\\.\\d{6}s -->/', '.123456s -->', $expectedText),
            preg_replace('/\\.\\d{6}s -->/', '.123456s -->', $page->getBody())
        );
    }
}
