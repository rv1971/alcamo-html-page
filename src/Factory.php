<?php

namespace alcamo\html_page;

use SebastianBergmann\Exporter\Exporter;
use alcamo\exception\FileLocation;
use alcamo\html_creation\element\{B, P, Ul};
use alcamo\modular_class\ModularClassTrait;
use alcamo\rdfa\{HasRdfaDataTrait, RdfaData};
use alcamo\url_creation\{
    HasUrlFactoryTrait,
    TrivialUrlFactory,
    UrlFactoryInterface
};
use alcamo\xml_creation\Nodes;

/**
 * @brief Factory for HTML code
 *
 * Implemented as a modular class using
 * alcamo::modular_class::ModularClassTrait.
 *
 * @date Last reviewed 2021-06-24
 */
class Factory implements \Countable, \Iterator, \ArrayAccess
{
    use HasRdfaDataTrait;
    use HasUrlFactoryTrait;
    use ModularClassTrait;

    /// Create XHTML by default
    public const DEFAULT_RDFA_DATA = [
        'dc:format' => 'application/xhtml+xml; charset="UTF-8"'
    ];

    public static function newFromRdfaData(
        iterable $rdfaData,
        ?array $modules = null,
        ?UrlFactoryInterface $urlFactory = null
    ) {
        return new static(
            RdfaData::newfromIterable($rdfaData),
            $modules,
            $urlFactory
        );
    }

    public function __construct(
        ?RdfaData $rdfaData = null,
        ?array $modules = null,
        ?UrlFactoryInterface $urlFactory = null
    ) {
        $this->rdfaData_ = RdfaData::newFromIterable(static::DEFAULT_RDFA_DATA);

        if (isset($rdfaData)) {
            $this->rdfaData_ = $this->rdfaData_->replace($rdfaData);
        }

        /** If no $urlFactory is given, create an insatnce of
         *  alcamo::url_creation::TrivialUrlFactory. */
        $this->urlFactory_ = $urlFactory ?? new TrivialUrlFactory();

        if (isset($modules)) {
            $this->addModules($modules);
        }

        /** If no `page` module is given, add a new instance of
         *  PageFactory. */
        if (!isset($this['page'])) {
            $this->addModule(new PageFactory());
        }
    }

    public function setRdfaData(RdfaData $rdfaData): void
    {
        $this->rdfaData_ = $rdfaData;
    }

    /// Render a throwable in a human-readable manner in all detail
    public function renderThrowable(\Throwable $e): Nodes
    {
        $exporter = new Exporter();

        $codeLocation = FileLocation::newFromThrowable($e);

        $result = [ new P([ new B(get_class($e)), " at $codeLocation" ]) ];

        $result[] = new P(new B($e->getMessage()));

        $props = [];

        foreach (get_object_vars($e) as $key => $value) {
            $props[] = "$key = " . $exporter->export($value);
        }

        if ($props) {
            $result[] = new Ul($props);
        }

        foreach ($e->getTrace() as $item) {
            $itemHtml = [ "{$item['function']}()" ];

            if (isset($item['file'])) {
                $itemHtml[] = ' in ' . FileLocation::newFromBacktraceItem($item);
            }

            $result[] = new P($itemHtml);
        }

        return new Nodes($result);
    }
}
