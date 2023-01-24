<?php

namespace alcamo\html_page;

use alcamo\rdfa\{
    DcAbstract,
    DcAlternative,
    DcAudience,
    DcCreator,
    DcSource,
    DcTitle,
    HttpCacheControl,
    MetaCharset,
    Node,
    OwlVersionInfo,
    RdfaData,
    RelContents,
    RelHome,
    RelUp,
    SimpleStmt,
    StmtInterface
};
use alcamo\html_creation\element\{A, Link, Meta, Title};
use alcamo\xml_creation\Nodes as HtmlNodes;
use PHPUnit\Framework\TestCase;

class Rdfa2HtmlTest extends TestCase
{
    /**
     * @dataProvider stmt2MetaProvider
     */
    public function testStmt2Meta($stmt, $expectedHtml): void
    {
        $rdfa2Html = new Rdfa2Html();

        if (isset($expectedHtml)) {
            $this->assertSame(
                get_class($expectedHtml),
                get_class($rdfa2Html->stmt2Meta($stmt))
            );
        }

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
                new Meta([ 'charset' => 'UTF-8' ])
            ],
            [
                new DcCreator('Alice'),
                new Meta(
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

        if (isset($expectedHtml)) {
            $this->assertSame(
                get_class($expectedHtml),
                get_class($rdfa2Html->stmt2Link($stmt))
            );
        }

        $this->assertEquals($expectedHtml, $rdfa2Html->stmt2Link($stmt));
    }

    public function stmt2LinkProvider(): array
    {
        return [
            [
                new DcCreator(new Node('http://bob.example.com')),
                new Link(
                    'http://bob.example.com',
                    [ 'rel' => 'dc:creator author']
                )
            ],
            [
                new DcSource(
                    new Node(
                        'http://de.example.com/quelle',
                        RdfaData::newFromIterable(
                            [ 'dc:language' => 'de-LI' ]
                        )
                    )
                ),
                new Link(
                    'http://de.example.com/quelle',
                    [
                        'rel' => 'dc:source canonical',
                        'hreflang' => 'de-LI'
                    ]
                )
            ],
            [
                new RelContents(
                    new Node(
                        'http://example.com/toc',
                        RdfaData::newFromIterable(
                            [
                                'dc:format' => 'application/pdf',
                                'dc:title' => 'Table of contents'
                            ]
                        )
                    )
                ),
                new Link(
                    'http://example.com/toc',
                    [
                        'rel' => 'contents',
                        'title' => 'Table of contents',
                        'type' => 'application/pdf'
                    ]
                )
            ],
            [
                new RelHome(new Node('http://example.com')),
                new Link('http://example.com', [ 'rel' => 'home' ])
            ],
            [
                new RelUp(
                    new Node(
                        'http://example.com/chapter',
                        RdfaData::newFromIterable(
                            [
                                'dc:title' => 'Chapter 42'
                            ]
                        )
                    )
                ),
                new Link(
                    'http://example.com/chapter',
                    [
                        'rel' => 'up',
                        'title' => 'Chapter 42'
                    ]
                )
            ],
            [
                new SimpleStmt(
                    'tag:example.com,2023:foo',
                    'foo',
                    'bar',
                    new Node('http://example.com/baz')
                ),
                null
            ]
        ];
    }

    /**
     * @dataProvider stmt2AProvider
     */
    public function testStmt2A($stmt, $expectedHtml): void
    {
        $rdfa2Html = new Rdfa2Html();

        if (isset($expectedHtml)) {
            $this->assertSame(
                get_class($expectedHtml),
                get_class($rdfa2Html->stmt2A($stmt))
            );
        }

        $this->assertEquals($expectedHtml, $rdfa2Html->stmt2A($stmt));
    }

    public function stmt2AProvider(): array
    {
        return [
            [
                new DcCreator(new Node('http://bob.example.com')),
                new A(
                    'Creator',
                    [
                        'href' => 'http://bob.example.com',
                        'rel' => 'dc:creator author'
                    ]
                )
            ],
            [
                new DcSource(
                    new Node(
                        'http://de.example.com/quelle',
                        RdfaData::newFromIterable(
                            [ 'dc:language' => 'de-LI' ]
                        )
                    )
                ),
                new A(
                    'Source',
                    [
                        'href' => 'http://de.example.com/quelle',
                        'rel' => 'dc:source canonical',
                        'hreflang' => 'de-LI'
                    ]
                )
            ],
            [
                new RelContents(
                    new Node(
                        'http://example.com/toc',
                        RdfaData::newFromIterable(
                            [
                                'dc:format' => 'application/pdf',
                                'dc:title' => 'Table of contents'
                            ]
                        )
                    )
                ),
                new A(
                    'Table of contents',
                    [
                        'href' => 'http://example.com/toc',
                        'rel' => 'contents',
                        'type' => 'application/pdf'
                    ]
                )
            ],
            [
                new RelHome(new Node('http://example.com')),
                new A(
                    'Home',
                    [ 'href' => 'http://example.com', 'rel' => 'home' ]
                )
            ],
            [
                new RelUp(
                    new Node(
                        'http://example.com/chapter',
                        RdfaData::newFromIterable(
                            [
                                'dc:title' => 'Chapter 42'
                            ]
                        )
                    )
                ),
                new A(
                    'Chapter 42',
                    [
                        'href' => 'http://example.com/chapter',
                        'rel' => 'up'
                    ]
                )
            ],
            [
                new SimpleStmt(
                    'tag:example.com,2023:foo',
                    'foo',
                    'bar',
                    new Node('http://example.com/baz')
                ),
                new A(
                    'Bar',
                    [ 'href' => 'http://example.com/baz' ]
                )
            ]
        ];
    }

    /**
     * @dataProvider rdfaData2HtmlProvider
     */
    public function testRdfaData2Html($rdfaInputData, $expectedHtml): void
    {
        $rdfa2Html = new Rdfa2Html();

        $rdfaData = RdfaData::newFromIterable($rdfaInputData);

        $this->assertEquals(
            new HtmlNodes($expectedHtml),
            $rdfa2Html->rdfaData2Html($rdfaData)
        );
    }

    public function rdfaData2HtmlProvider(): array
    {
        return [
            [
                [
                    'dc:title' => 'Lorem ipsum',
                    'dc:alternative' => 'At vero eos',
                    'dc:format' => 'text/html; charset=US-ASCII',
                    'dc:audience' =>
                    new Node('https://example.com/premium-customers'),
                    'dc:abstract' =>
                    'Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
                ],
                [
                    new Meta([ 'charset' => 'US-ASCII' ]),
                    new Title('Lorem ipsum', [ 'property' => 'dc:title' ]),
                    new Meta(
                        [
                            'property' => 'dc:alternative',
                            'content' => 'At vero eos'
                        ]
                    ),
                    new Link(
                        'https://example.com/premium-customers',
                        [ 'rel' => 'dc:audience' ]
                    ),
                    new Meta(
                        [
                            'property' => 'dc:abstract',
                            'name' => 'description',
                            'content' =>
                            'Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
                        ]
                    )
                ]
            ]
        ];
    }

    /**
     * @dataProvider rdfaData2NsAttrsProvider
     */
    public function testRdfaData2NsAttrs($rdfaInputData, $expectedAttrs): void
    {
        $rdfa2Html = new Rdfa2Html();

        $rdfaData = RdfaData::newFromIterable($rdfaInputData);

        $this->assertEquals(
            $expectedAttrs,
            $rdfa2Html->rdfaData2NsAttrs($rdfaData)
        );
    }

    public function rdfaData2NsAttrsProvider(): array
    {
        return [
            [
                [
                    'dc:title' => 'Lorem ipsum',
                    'http:cache-control' => 'public',
                    'owl:versionInfo' => '1.42'
                ],
                [
                    'xmlns:dc' => DcTitle::PROP_NS_NAME,
                    'xmlns:owl' => OwlVersionInfo::PROP_NS_NAME
                ]
            ]
        ];
    }
}
