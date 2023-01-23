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
use alcamo\html_creation\Element;
use alcamo\html_creation\element\{Link, Meta, Title};
use alcamo\xml_creation\Nodes as HtmlNodes;

class Rdfa2html
{
    public const PROP_URI_TO_META_NAME = [
        DcAbstract::PROP_URI => 'description',
        DcCreator::PROP_URI  => 'author'
    ];

    public const PROP_URI_TO_HTML_REL = [
        DcCreator::PROP_URI   => 'author',
        DcSource::PROP_URI    => 'canonical',
        RelContents::PROP_URI => 'contents',
        RelHome::PROP_URI     => 'home',
        RelUp::PROP_URI       => 'up'
    ];

    /**
     * @param $stmt Statement whose object is not a Node.
     *
     * @return Title|Meta|null
     */
    public function stmt2Meta(StmtInterface $stmt): ?Element
    {
        $uri = $stmt->getPropUri();

        /* Do not include proprietary properties into HTML. */
        $attrs = substr($uri, 0, 4) != 'tag:'
            ? [ 'property' => $stmt->getCurie() ]
            : [];

        switch ($uri)
        {
            case DcTitle::PROP_URI:
                return new Title((string)$stmt, $attrs);

            case MetaCharset::PROP_URI:
                $attrs['charset'] = (string)$stmt;

                return new Meta(null, $attrs);

            default:
                $metaName = static::PROP_URI_TO_META_NAME[$uri] ?? null;

                if (isset($metaName)) {
                    $attrs['name'] = $metaName;
                }

                /* Return a Meta obejct iff property or name are set. */
                if ($attrs) {
                    $attrs['content'] = (string)$stmt;

                    return new Meta(null, $attrs);
                } else {
                    return null;
                }
        }
    }

    /** @param $stmt Statement whose object is a Node. */
    public function stmt2Link(StmtInterface $stmt): ?Link
    {
        $uri = $stmt->getPropUri();

        /* Do not include proprietary properties into HTML. */
        $rel = substr($uri, 0, 4) != 'tag:'
            ? $stmt->getPropCurie()
            : null;

        $htmlRel = static::PROP_URI_TO_HTML_REL[$uri] ?? null;

        if (isset($htmlRel)) {
            $rel = isset($rel) ? "$rel $htmlRel" : $htmlRel;
        }

        if (!isset($rel)) {
            return null;
        }

        $node = $stmt->getObject();

        $attrs = [ 'href' => $uri, 'rel' => $rel ];

        $rdfaData = $node->getRdfaData();

        if (isset($rdfaData)) {
            $attrs['type'] = $rdfaData['dc:format'] ?? null;

            $attrs['hreflang'] = $rdfaData['dc:language'] ?? null;

            $attrs['title'] = $rdfaData['dc:title'] ?? null;
        }

        return new Link(null, $attrs);
    }

    /** @param $stmt Statement whose object is a Node. */
    public function stmt2A(StmtInterface $stmt): A
    {
        $node = $stmt->getObject();

        $attrs = [
            'href' => $node->getUri(),
            'rel' => $stmt->getPropCurie()
        ];

        $htmlRel = static::PROP_URI_TO_HTML_REL[$stmt->getPropUri()] ?? null;

        if (isset($htmlRel)) {
            $attrs['rel'] .= " $htmlRel";
        }

        $rdfaData = $node->getRdfaData();

        if (isset($rdfaData)) {
            $attrs['type'] = $rdfaData['dc:format'] ?? null;

            $attrs['hreflang'] = $rdfaData['dc:language'] ?? null;

            $title = $rdfaData['dc:title'] ?? null;
        }

        return new A($title ?? ucfirst($node->getPropLocalName()), $attrs);
    }

    public function rdfaData2Html(RdfaData $rdfaData): HtmlNodes
    {
        $htmlNodes = [];

        /** Process `meta:charset` first, if present. */
        if (isset($rdfaData['meta:charset'])) {
            $htmlNodes[] = $this->stmt2Meta($rdfaData['meta:charset']);
        }

        foreach ($rdfaData as $stmts) {
            foreach (is_array($stmts) ? $stmts : [ $stmts ] as $stmt) {
                if ($stmt->getUri() != MetaCharset::PROP_URI) {
                    $htmlNodes[] = $stmt->getObject() instanceof Node
                        ? $this->stmt2Link($stmt)
                        : $this->stmt2Meta($stmt);
                }
            }
        }

        return new Nodes($htmlNodes);
    }

    public function rdfaData2NsAttrs(RdfaData $rdfaData): array
    {
        $attrs = [];

        foreach ($rdfaData->createNamespaceMap() as $prefix => $nsName) {
            $attrs["xmlns:$prefix"] = $nsName;
        }

        return $attrs;
    }
}
