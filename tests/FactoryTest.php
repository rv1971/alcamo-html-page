<?php

namespace alcamo\html_page;

use PHPUnit\Framework\TestCase;
use alcamo\modular_class\ModuleTrait;
use alcamo\url_creation\DirMapUrlFactory;
use alcamo\xml_creation\{Comment, Nodes};

class FooModule
{
    use ModuleTrait;

    public const NAME = 'foo';

    public $text = 'ut labore et dolore magna aliquyam erat';
}

class FactoryTest extends TestCase
{
    public function testConstruct()
    {
        $pageFactory = new PageFactory(
            new ResourceFactory(
                new DirMapUrlFactory(__DIR__, '/content')
            )
        );

        $factory = new Factory(
            null,
            [ new FooModule(), $pageFactory ],
            new DirMapUrlFactory(__DIR__, 'foo-bar')
        );

        $this->assertSame('foo-bar', $factory->getUrlFactory()->getHtdocsUrl());

        $this->assertSame(
            'ut labore et dolore magna aliquyam erat',
            $factory['foo']->text
        );

        $this->assertSame(
            '/content',
            $factory['page']->getResourceFactory()->getUrlFactory()
                ->getHtdocsUrl()
        );
    }

    /**
     * @dataProvider htmlGenerationProvider
     */
    public function testHtmlGeneration(
        $rdfaData,
        $urlFactory,
        $resources,
        $extraHeadNodes,
        $expectedHtml
    ) {
        $factory = Factory::newFromRdfaData($rdfaData, null, $urlFactory);

        $html = $factory['page']->createBegin($resources, $extraHeadNodes)
            . 'Lorem ipsum.'
            . $factory['page']->createEnd();

        $maskedHtml = preg_replace('/\\.\\d{6}s -->/', '.123456s -->', $html);

        $this->assertSame($expectedHtml, $maskedHtml);
    }

    public function htmlGenerationProvider()
    {
        $cssPath = __DIR__ . DIRECTORY_SEPARATOR . 'alcamo.css';

        $jsonPath = __DIR__ . DIRECTORY_SEPARATOR . 'alcamo.json';

        $jsPath = __DIR__ . DIRECTORY_SEPARATOR . 'alcamo.js';

        $mCssGz = gmdate('YmdHis', filemtime("$cssPath.gz"));

        $mJson = gmdate('YmdHis', filemtime($jsonPath));

        $mJsGz = gmdate('YmdHis', filemtime("$jsPath.gz"));

        return [
            'simple' => [
                [ 'dc:title' => 'Foo | Bar' ],
                null,
                null,
                null,
                '<!DOCTYPE html>'
                . '<html xmlns="http://www.w3.org/1999/xhtml" '
                . 'xmlns:dc="http://purl.org/dc/terms/">'
                . '<head>'
                . '<meta charset="UTF-8"/>'
                . '<title property="dc:title">Foo | Bar</title>'
                . '</head>'
                . '<body>Lorem ipsum.</body>'
                . '<!-- Served in 0.123456s -->'
                . '</html>'
            ],
            'with-metadata-and-resources' => [
                [
                    'dc:identifier' => 'baz.qux',
                    'dc:language' => 'en-UG',
                    'owl:versionInfo' => '42.43.44'
                ],
                new DirMapUrlFactory(__DIR__, '/'),
                [
                    $cssPath,
                    $jsPath,
                    [ $jsonPath, 'manifest' ]
                ],
                new Nodes(new Comment('consetetur sadipscing elitr')),
                '<!DOCTYPE html>'
                . '<html xmlns="http://www.w3.org/1999/xhtml" '
                . 'xmlns:dc="http://purl.org/dc/terms/" '
                . 'xmlns:owl="http://www.w3.org/2002/07/owl#" '
                . 'id="baz.qux" lang="en-UG">'
                . '<head>'
                . '<meta charset="UTF-8"/>'
                . '<meta property="dc:identifier" content="baz.qux"/>'
                . '<meta property="dc:language" content="en-UG"/>'
                . '<meta property="owl:versionInfo" content="42.43.44"/>'
                . "<link rel=\"stylesheet\" href=\"/alcamo.css.gz?m=$mCssGz\"/>"
                . "<script src=\"/alcamo.js.gz?m=$mJsGz\"></script>"
                . "<link type=\"application/json\" rel=\"manifest\" href=\"/alcamo.json?m=$mJson\"/>"
                . '<!-- consetetur sadipscing elitr -->'
                . '</head>'
                . '<body>Lorem ipsum.</body>'
                . '<!-- Served in 0.123456s -->'
                . '</html>'
            ]
        ];
    }

    /**
     * @dataProvider renderThrowableProvider
     */
    public function testRenderThrowable($factory, $throwable, $expectedString)
    {
        $this->assertSame(
            $expectedString,
            substr(
                $factory->renderThrowable($throwable),
                0,
                strlen($expectedString)
            )
        );
    }

    public function renderThrowableProvider()
    {
        $factory = new Factory();

        $eSimple = (function () {
            return new \Exception('Lorem ipsum');
        })();

        $eWithProps = new \LogicException('consetetur sadipscing elitr');
        $eWithProps->intValue = 42;
        $eWithProps->stringValue = 'foo';

        $htmlDoc = new \DOMDocument();

        $htmlDoc->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<div xmlns="http://www.w3.org/1999/xhtml"><b>ut labore et dolore magna</b></div>'
        );

        $eWithHtmlElemProp =
            new \UnexpectedValueException('tempor invidunt');
        $eWithHtmlElemProp->extraMessage = $htmlDoc->documentElement->firstChild;

        $domDoc = new \DOMDocument();

        $domDoc->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?><foo><bar baz="qux"/></foo>'
        );

        $eWithDomNodeProp =
            new \UnexpectedValueException('sed diam nonumy eirmod');
        $eWithDomNodeProp->inData = $domDoc->documentElement->firstChild;

        return [
            'simple' => [
                $factory,
                $eSimple,
                '<p><b>' . \Exception::class . '</b> at ' . __FILE__ . ':156</p>'
                . '<p><b>Lorem ipsum</b></p>'
                . '<p>alcamo\html_page\{closure}() in ' . __FILE__ . ':157</p>'
            ],
            'with-props' => [
                $factory,
                $eWithProps,
                '<p><b>' . \LogicException::class . '</b> at ' . __FILE__ . ':159</p>'
                . '<p><b>consetetur sadipscing elitr</b></p>'
                . '<ul><li>intValue = 42</li>'
                . "<li>stringValue = 'foo'</li></ul>"
                . '<p>renderThrowableProvider()</p>'
            ],
            'with-html-element-prop' => [
                $factory,
                $eWithHtmlElemProp,
                '<p><b>' . \UnexpectedValueException::class . '</b> at ' . __FILE__ . ':171</p>'
                . '<p><b>tempor invidunt</b></p>'
                . '<ul><li>extraMessage = <b>ut labore et dolore magna</b></li></ul>'
                . '<p>renderThrowableProvider()</p>'
            ],
            'with-dom-node-prop' => [
                $factory,
                $eWithDomNodeProp,
                '<p><b>' . \UnexpectedValueException::class . '</b> at ' . __FILE__ . ':181</p>'
                . '<p><b>sed diam nonumy eirmod</b></p>'
                . '<ul><li>inData = &lt;bar baz="qux"/&gt;</li></ul>'
                . '<p>renderThrowableProvider()</p>'
            ]
        ];
    }
}
