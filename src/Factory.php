<?php

namespace alcamo\html_page;

use SebastianBergmann\Exporter\Exporter;
use alcamo\exception\ExceptionInterface;
use alcamo\html_creation\{
    FileResourceFactoryInterface,
    SimpleFileResourceFactory
};
use alcamo\html_creation\element\{B, P, Ul};
use alcamo\modular_class\ModularClassTrait;
use alcamo\rdfa\RdfaData;
use alcamo\xml_creation\{Nodes, Raw};

/**
 * @brief Factory for HTML code
 *
 * Implemented as a modular class using
 * alcamo::modular_class::ModularClassTrait.
 */
class Factory implements \Countable, \Iterator, \ArrayAccess
{
    use ModularClassTrait;

    /// Create XHTML by default
    public const DEFAULT_RDFA_DATA = [
        'dc:format' => 'application/xhtml+xml; charset="UTF-8"'
    ];

    private $rdfaData_;            ///< RdfaData
    private $fileResourceFactory_; ///< FileResourceFactoryInterface

    public static function newFromRdfaData(
        iterable $rdfaData,
        ?array $modules = null,
        ?FileResourceFactoryInterface $fileResourceFactory = null
    ) {
        return new static(
            RdfaData::newfromIterable($rdfaData),
            $modules,
            $fileResourceFactory
        );
    }

    public function __construct(
        ?RdfaData $rdfaData = null,
        ?array $modules = null,
        ?FileResourceFactoryInterface $fileResourceFactory = null
    ) {
        $this->rdfaData_ = RdfaData::newFromIterable(static::DEFAULT_RDFA_DATA);

        if (isset($rdfaData)) {
            $this->rdfaData_ = $this->rdfaData_->replace($rdfaData);
        }

        /** If no $fileResourceFactory is given, create an instance of
         *  alcamo::html_creation::SimpleFileResourceFactory. */
        $this->fileResourceFactory_ =
            $fileResourceFactory ?? new SimpleFileResourceFactory();

        if (isset($modules)) {
            $this->addModules($modules);
        }

        /** If no `page` module is given, add a new instance of
         *  PageFactory. */
        if (!isset($this['page'])) {
            $this->addModule(new PageFactory());
        }
    }

    public function getRdfaData(): RdfaData
    {
        return $this->rdfaData_;
    }

    public function getFileResourceFactory(): FileResourceFactoryInterface
    {
        return $this->fileResourceFactory_;
    }

    public function setRdfaData(RdfaData $rdfaData): void
    {
        $this->rdfaData_ = $rdfaData;
    }

    /// Render a throwable in a human-readable manner in all detail
    public function renderThrowable(\Throwable $e): Nodes
    {
        $exporter = new Exporter();

        $result = [
            new P(
                [ new B(get_class($e)), " at {$e->getFile()}:{$e->getLine()}" ]
            )
        ];

        $result[] = new P(new B($e->getMessage()));

        $props = [];

        foreach (get_object_vars($e) as $key => $value) {
            switch (true) {
                case $value instanceof \DOMElement
                    && $value->namespaceURI == 'http://www.w3.org/1999/xhtml':
                    $displayValue =
                        new Raw($value->ownerDocument->saveXML($value));
                    break;

                case $value instanceof \DOMNode:
                    $displayValue = $value->ownerDocument->saveXML($value);
                    break;

                default:
                    $displayValue = $exporter->export($value);
            }

            $props[] = [ "$key = ", $displayValue ];
        }

        if ($e instanceof ExceptionInterface) {
            foreach ($e->getMessageContext() as $key => $value) {
                $props[] = [ "$key = ", $value ];
            }
        }

        if ($props) {
            $result[] = Ul::newFromItems($props);
        }

        foreach ($e->getTrace() as $item) {
            $itemHtml = [ "{$item['function']}()" ];

            if (isset($item['file'])) {
                $itemHtml[] = " in {$item['file']}:{$item['line']}";
            }

            $result[] = new P($itemHtml);
        }

        return new Nodes($result);
    }
}
