<?php

namespace alcamo\html_page;

use alcamo\rdfa\{
    DcAbstract,
    DcCreator,
    DcSource,
    DcTitle,
    MetaCharset,
    Node,
    RdfaData,
    RelContents,
    RelHome,
    RelUp,
    StmtInterface
};
use alcamo\html_creation\element\{Link, Meta, Title};
use PHPUnit\Framework\TestCase;

class Rdfa2HtmlTest extends TestCase
{
    /**
     * @dataProvider stmt2MetaProvider
     */
    public function testStmt2Meta($stmt, $expectedHtml): void
    {
        $rdfa2Html = new Rdfa2Html();

        $this->assertSame(
            get_class($expectedHtml),
            get_class($rdfa2Html->stmt2Meta($stmt))
        );

        $this->assertEquals($expectedHtml, $rdfa2Html->stmt2Meta($stmt));
    }

    public function stmt2MetaProvider(): array
    {
        return [
            [
                new DcTitle('Lorem ipsum'),
                new Title('Lorem ipsum', [ 'property' => 'dc:title' ])
            ],
            [
                new MetaCharset('UTF-8'),
                new Meta(null, [ 'charset' => 'UTF-8' ])
            ],
            [
                new DcCreator('Alice'),
                new Meta(
                    null,
                    [
                        'name' => 'author',
                        'property' => 'dc:creator',
                        'content' => 'Alice'
                    ]
                )
            ],
            [
                new DcAbstract(
                    'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                ),
                new Meta(
                    null,
                    [
                        'name' => 'description',
                        'property' => 'dc:abstract',
                        'content' =>
                        'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                    ]
                )
            ],
            [
                new DcAlternative('At vero eos'),
                new Meta(
                    null,
                    [
                        'property' => 'dc:alternative',
                        'content' => 'At vero eos'
                    ]
                )
            ],
            [
                new HttpCacheControl('private'),
                null
            ]
        ];
    }

    /**
     * @dataProvider stmt2LinkProvider
     */
    public function testStmt2Link($stmt, $expectedHtml): void
    {
        $rdfa2Html = new Rdfa2Html();

        $this->assertSame(
            get_class($expectedHtml),
            get_class($rdfa2Html->stmt2Link($stmt))
        );

        $this->assertEquals($expectedHtml, $rdfa2Html->stmt2Link($stmt));
    }

    public function stmt2LinkProvider(): array
    {
        return [
            /** @todo */
        ];
    }
}
