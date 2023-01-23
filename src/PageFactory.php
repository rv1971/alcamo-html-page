<?php

namespace alcamo\html_page;

use alcamo\modular_class\ModuleTrait;
use alcamo\html_creation\element\{Body, Head, Html};
use alcamo\xml_creation\{Comment, DoctypeDecl, Nodes};

/**
 * @brief HTML code factory module for the enitre page
 */
class PageFactory
{
    use ModuleTrait {
        init as moduleInit;
    }

    /// Module name
    public const NAME = 'page';

    /// Default attributes for the \<html> element
    public const DEFAULT_HTML_ATTRS = [
        'xmlns' => 'http://www.w3.org/1999/xhtml'
    ];

    /// Default attributes for the \<head> element
    public const DEFAULT_HEAD_ATTRS = [];

    /// Default attributes for the \<body> element
    public const DEFAULT_BODY_ATTRS = [];

    private $created_;         ///< Microtime of creation of this object
    private $resourceFactory_; ///< ResourceFactory
    private $rdfa2Html_;       ///< Rdfa2Html

    public function __construct(?ResourceFactory $resourceFactory = null)
    {
        $this->created_ = microtime(true);
        $this->resourceFactory_ = $resourceFactory;
        $this->rdfa2Html_ = new Rdfa2Html();
    }

    public function getResourceFactory(): ?ResourceFactory
    {
        return $this->resourceFactory_;
    }

    /// If no resource factory has been given, create a new ResourceFactory
    public function init(Factory $factory)
    {
        $this->moduleInit($factory);

        if (!isset($this->resourceFactory_)) {
            $this->resourceFactory_ =
                new ResourceFactory($this->getUrlFactory());
        }
    }

    /// Return seconds elapsed since creation.
    public function elapsed(): float
    {
        return microtime(true) - $this->created_;
    }

    public function createDoctypeDecl($intSubset = null): DoctypeDecl
    {
        return new DoctypeDecl('html', null, $intSubset);
    }

    /// Default attributes for the \<html> element
    public function createDefaultHtmlAttrs(): array
    {
        /**
         * - namespace prefixes needed for RDFa data
         * - @ref DEFAULT_HTML_ATTRS
         */
        $attrs = $this->rdfa2Html_->rdfaData2NsAttrs($this->rdfaData_)
            + static::DEFAULT_HTML_ATTRS;

        /** - `id` from `dc:identifier` if present in the RDFa data. */
        if (isset($this->rdfaData_['dc:identifier'])) {
            $attrs['id'] = $this->rdfaData_['dc:identifier'];
        }

        /** - `lang` from `dc:language` if present in the RDFa data. */
        if (isset($this->rdfaData_['dc:language'])) {
            $attrs['lang'] = $this->rdfaData_['dc:language'];
        }

        return $attrs;
    }

    /// Opening \<html> tag
    public function createHtmlOpen(?array $attrs = null): string
    {
        /** Use createDefaultHtmlAttrs(), potentially overriden by $attrs. */
        return
            (new Html(null, (array)$attrs + $this->createDefaultHtmlAttrs()))
            ->createOpeningTag();
    }

    /// Default attributes for the \<head> element
    public function createDefaultHeadAttrs(): array
    {
        /** Return @ref DEFAULT_HEAD_ATTRS. */
        return static::DEFAULT_HEAD_ATTRS;
    }

    /// \<head> element
    public function createHead(
        ?array $resources = null,
        ?Nodes $extraNodes = null,
        ?array $attrs = null
    ): Head {
        /** - HTML nodes created from the RDFa data. */
        $content = [ $this->rdfa2Html_->rdfaData2Html($this->rdfaData_) ];

        /** - HTML nodes created from $resources */
        if (isset($resources)) {
            $content[] =
                $this->resourceFactory_->createElementsFromItems($resources);
        }

        /** - Extra nodes, if any. */
        if (isset($extraNodes)) {
            $content[] = $extraNodes;
        }

        /** Use createDefaultHeadAttrs(), potentially overriden by $attrs. */
        return
            new Head($content, (array)$attrs + $this->createDefaultHeadAttrs());
    }

    /// Default attributes for the \<head> element
    public function createDefaultBodyAttrs(): array
    {
        /** Return @ref DEFAULT_BODY_ATTRS. */
        return static::DEFAULT_BODY_ATTRS;
    }

    /// Opening \<body> tag
    public function createBodyOpen(?array $attrs = null): string
    {
        /** Use createDefaultBodyAttrs(), potentially overriden by $attrs. */
        return
            (new Body(null, (array)$attrs + $this->createDefaultBodyAttrs()))
            ->createOpeningTag();
    }

    /**
     * @brief Create beginning of a page
     *
     * - doctype declaration
     * - opening \<html> tag
     * - \<head> element
     * - opening \<body> tag
     */
    public function createBegin(
        ?array $resources = null,
        ?Nodes $extraHeadNodes = null,
        ?array $bodyAttrs = null
    ): string {
        return $this->createDoctypeDecl()
            . $this->createHtmlOpen()
            . $this->createHead($resources, $extraHeadNodes)
            . $this->createBodyOpen($bodyAttrs);
    }

    /**
     * @brief Create end of a page
     *
     * - closing \</body> tag
     * - comment telling elapsed time
     * - closing \</html> tag
     */
    public function createEnd(): string
    {
        return
            (new Body())->createClosingTag()
            . (new Comment(sprintf("Served in %.6fs", $this->elapsed())))
            . (new Html())->createClosingTag();
    }
}
