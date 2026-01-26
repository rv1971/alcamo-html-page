<?php

namespace alcamo\html_page;

use PHPUnit\Framework\TestCase;
use alcamo\html_creation\element\{Icon, Link, Script, Stylesheet};
use alcamo\url_creation\DirMapUrlFactory;

class ResourceFactoryTest extends TestCase
{
  /**
   * @dataProvider createElementsFromItemsProvider
   */
    public function testCreateElementsFromItems(
        $urlFactory,
        $items,
        $expectedClasses,
        $expectedString
    ) {
        $factory = new ResourceFactory($urlFactory);
        $nodes = $factory->createElementsFromItems($items);

        $this->assertSame(count($items), count($nodes));

        $i = 0;

        foreach ($nodes as $node) {
            $this->assertInstanceOf($expectedClasses[$i++], $node);
        }

        $this->assertEquals($expectedString, (string)$nodes);
    }

    public function createElementsFromItemsProvider()
    {
        $cssPath = __DIR__ . DIRECTORY_SEPARATOR . 'alcamo.css';

        $jsonPath = __DIR__ . DIRECTORY_SEPARATOR . 'alcamo.json';

        $jsPath = __DIR__ . DIRECTORY_SEPARATOR . 'alcamo.js';

        $mjsPath = __DIR__ . DIRECTORY_SEPARATOR . 'alcamo.mjs';

        $png16Path = __DIR__ . DIRECTORY_SEPARATOR . 'alcamo-16.png';

        $svgPath = __DIR__ . DIRECTORY_SEPARATOR . 'alcamo.svg';

        $mCss = gmdate('YmdHis', filemtime($cssPath));

        $mCssGz = gmdate('YmdHis', filemtime("$cssPath.gz"));

        $mJson = gmdate('YmdHis', filemtime($jsonPath));

        $mJs = gmdate('YmdHis', filemtime($jsPath));

        $mMjs = gmdate('YmdHis', filemtime($mjsPath));

        $mJsGz = gmdate('YmdHis', filemtime("$jsPath.gz"));

        $mPng16 = gmdate('YmdHis', filemtime($png16Path));

        $mSvg = gmdate('YmdHis', filemtime($svgPath));

        $mSvgz = gmdate('YmdHis', filemtime("${svgPath}z"));

        return [
            'simple' => [
                new DirMapUrlFactory(__DIR__, '/test/', true, true),
                [
                    $cssPath,
                    [ $jsonPath, 'manifest' ],
                    $jsPath,
                    $mjsPath,
                    $png16Path,
                    $svgPath
                ],
                [
                    Stylesheet::class,
                    Link::class,
                    Script::class,
                    Script::class,
                    Icon::class,
                    Icon::class
                ],
                "<link href=\"/test/alcamo.css?m=$mCss\" rel=\"stylesheet\"/>"
                . "<link type=\"application/json\" rel=\"manifest\" href=\"/test/alcamo.json?m=$mJson\"/>"
                . "<script src=\"/test/alcamo.js?m=$mJs\" type=\"application/javascript\"></script>"
                . "<script src=\"/test/alcamo.mjs?m=$mMjs\" type=\"module\"></script>"
                . "<link type=\"image/png\" sizes=\"16x16\" "
                . "href=\"/test/alcamo-16.png?m=$mPng16\" rel=\"icon\"/>"
                . "<link type=\"image/svg+xml\" sizes=\"any\" "
                . "href=\"/test/alcamo.svg?m=$mSvg\" rel=\"icon\"/>"
            ],
            'gz-with-attrs' => [
                new DirMapUrlFactory(__DIR__, '/test/'),
                [
                    [ $jsPath, [ 'id' => 'JS' ] ],
                    $cssPath,
                    [ $jsonPath, [ 'rel' => 'dc:relation' ] ],
                    $png16Path,
                    $svgPath
                ],
                [
                    Script::class,
                    Stylesheet::class,
                    Link::class,
                    Icon::class,
                    Icon::class
                ],
                "<script src=\"/test/alcamo.js.gz?m=$mJsGz\" type=\"application/javascript\" id=\"JS\"></script>"
                . "<link href=\"/test/alcamo.css.gz?m=$mCssGz\" rel=\"stylesheet\"/>"
                . "<link type=\"application/json\" rel=\"dc:relation\" "
                . "href=\"/test/alcamo.json?m=$mJson\"/>"
                . "<link type=\"image/png\" sizes=\"16x16\" "
                . "href=\"/test/alcamo-16.png?m=$mPng16\" rel=\"icon\"/>"
                . "<link type=\"image/svg+xml\" sizes=\"any\" "
                . "href=\"/test/alcamo.svgz?m=$mSvgz\" rel=\"icon\"/>"
            ]
        ];
    }
}
