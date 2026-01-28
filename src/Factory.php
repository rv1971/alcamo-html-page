<?php

namespace alcamo\html_page;

use SebastianBergmann\Exporter\Exporter;
use alcamo\decorator\MultiDecoratedArrayAccessTrait;
use alcamo\exception\{ExceptionInterface, InvalidType};
use alcamo\html_creation\{
    FileResourceFactoryInterface,
    Rdfa2Html,
    ResourceLinkFactory,
    SimpleFileResourceFactory
};
use alcamo\html_creation\element\{B, P, Ul};
use alcamo\rdfa\RdfaData;
use alcamo\xml_creation\{Nodes, Raw};

/**
 * @namespace alcamo::html_page
 *
 * @brief Modular factory for HTML pages
 */

/**
 * @brief Factory for HTML code
 *
 * Using alcamo::decorator::MultiDecoratedArrayAccessTrait, it is possble to
 * extend this class adding decorators.
 */
class Factory implements \ArrayAccess
{
    use MultiDecoratedArrayAccessTrait;

    /// Default RDFa data that may be overridden by explicit settings
    public const DEFAULT_RDFA_DATA = [
        'dc:format' => 'application/xhtml+xml; charset="UTF-8"'
    ];

    private $rdfaData_;            ///< RdfaData
    private $resourceLinkFactory_; ///< ResourceLinkFactory
    private $rdfa2Html_;           ///< Rdfa2Html

    /**
     * @param RdfaData|iterable RDFa data of the document.
     *
     * @param $decorators Further decorators to add.
     *
     * @param $resourceFactory ResourceLinkFactory|FileResourceFactoryInterface|null
     * - If ResourceLinkFactory store it as-is.
     * - If FileResourceFactoryInterface, create a ResourceLinkFactory from it.
     * - If `null`, create a default ResourceLinkFactory
     */
    public function __construct(
        $rdfaData = null,
        ?array $decorators = null,
        $resourceFactory = null,
        ?Rdfa2Html $rdfa2Html = null
    ) {
        $this->rdfaData_ = RdfaData::newFromIterable(static::DEFAULT_RDFA_DATA);

        if (isset($rdfaData)) {
            $this->rdfaData_ = $this->rdfaData_->replace(
                $rdfaData instanceof RdfaData
                    ? $rdfaData
                    : RdfaData::newFromIterable($rdfaData)
            );
        }

        if (isset($decorators)) {
            $this->addDecorators($decorators);
        }

        /** If no PageFactory decorator is given, add a new instance of
         *  PageFactory. */
        if (!isset($this[PageFactory::class])) {
            $this->addDecorator(new PageFactory());
        }

        switch (true) {
            case !isset($resourceFactory):
                $this->resourceLinkFactory_ = new ResourceLinkFactory();
                break;

            case $resourceFactory instanceof FileResourceFactoryInterface:
              $this->resourceLinkFactory_ =
                  new ResourceLinkFactory($resourceFactory);
              break;

            case $resourceFactory instanceof ResourceLinkFactory:
                $this->resourceLinkFactory_ = $resourceFactory;
                break;

            default:
                /** @throw alcamo::exception::InvalidType if $resourceFactory
                 *  is not one of the supported types. */
                throw (new InvalidType())->setMessageContext(
                    [ 'value' => $resourceFactory ]
                );
        }

        $this->rdfa2Html_ = $rdfa2Html ?? new Rdfa2Html();
    }

    public function getRdfaData(): RdfaData
    {
        return $this->rdfaData_;
    }

    public function getResourceLinkFactory(): ?ResourceLinkFactory
    {
        return $this->resourceLinkFactory_;
    }

    public function getRdfa2Html(): Rdfa2Html
    {
        return $this->rdfa2Html_;
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
