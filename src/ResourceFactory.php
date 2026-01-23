<?php

namespace alcamo\html_page;

use alcamo\html_creation\Element;
use alcamo\html_creation\element\{
    AbstractSpecificElement,
    Icon,
    Link,
    Script,
    Stylesheet
};
use alcamo\rdfa\MediaType;
use alcamo\url_creation\{HasUrlFactoryTrait, UrlFactoryInterface};
use alcamo\xml_creation\Nodes;

/**
 * @brief Factory for Icon, Link, Script and Stylesheet objects
 *
 * @date Last reviewed 2021-06-24
 */
class ResourceFactory
{
    use HasUrlFactoryTrait;

    public function __construct(UrlFactoryInterface $urlFactory)
    {
        $this->urlFactory_ = $urlFactory;
    }

    /// Create an HTML element from a local file path
    public function createElementFromPath(
        string $path,
        ?array $attrs = null
    ): Element {
        /** Determine the media type from filename unless the type is set in
         *  $attrs. Transform the `type` attribute into a MediaType object if
         *  it looks like one. Currently anything containing a slash is
         *  considered to look like a media type. The distinction is needed to
         *  allow the value `module` for the `type` attribute in `\<script>`
         *  elements. */
        $type = isset($attrs['type'])
            ? ($attrs['type'] instanceof MediaType
               || strpos($attrs['type'], '/') === false
               ? $attrs['type']
               : MediaType::newFromString($attrs['type']))
            : MediaType::newFromFilename($path);

        /** Create a URL from $path using the UrlFactoryInterface object. */
        $url = $this->urlFactory_->createFromPath($path);

        switch ($type->getType()) {
            case 'image':
                /** Create an Icon if $path is an image file. */
                return Icon::newFromLocalUrl(
                    $url,
                    compact('type') + (array)$attrs,
                    $path
                );
        }

        switch ($type->getTypeAndSubtype()) {
            /** Create a Script if $path is a JavaScript file. Set the
             *  `type`to `module` for a `*.mjs` file. */
            case 'application/javascript':
                if (
                    !isset($attrs['type'])
                    && pathinfo($path, PATHINFO_EXTENSION) == 'mjs'
                ) {
                    $attrs['type'] = 'module';
                }

                return Script::newFromLocalUrl($url, $attrs, $path);

            /** Create a Stylesheet if $path is a CSS file. */
            case 'text/css':
                return Stylesheet::newFromLocalUrl($url, $attrs, $path);

            /** In all other cases, create a Link. $attrs['rel'] must be
             *  set in this case. */
            default:
                return Link::newFromLocalUrl($url, $attrs, $path);
        }
    }

    /// Create HTML elements from an iterable
    public function createElementsFromItems(iterable $items): Nodes
    {
        $nodes = [];

        foreach ($items as $item) {
            switch (true) {
                /** - If an item is an HTML element, use it as-is. */
                case $item instanceof AbstractSpecificElement:
                    $nodes[] = $item;
                    break;

                 /** - If an item is an array, then take the first element as
                 *    the path. If the second element is an array, take it as
                 *    an array of attributes, otherwise as the value for the
                 *    `rel` attribute. */
                case is_array($item):
                    $nodes[] = $this->createElementFromPath(
                        $item[0],
                        isset($item[1])
                            ? (is_array($item[1])
                               ? $item[1]
                               : [ 'rel' => $item[1] ])
                            : null
                    );

                    break;

                /** In all other cases, take the item as the path. */
                default:
                    $nodes[] = $this->createElementFromPath($item);
            }
        }

        return new Nodes($nodes);
    }
}
