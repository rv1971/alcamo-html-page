<?php

namespace alcamo\html_page;

use alcamo\rdfa\{
    DcAbstract,
    DcCreator,
    DcFormat,
    DcSource,
    DcTitle,
    Node,
    RdfaData,
    StmtInterface,
    XhvMetaStmt
};
use alcamo\html_creation\Element;
use alcamo\html_creation\element\{A, Link, Meta, Title};
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
        XhvMetaStmt::PROP_NS_NAME . 'contents' => 'contents',
        XhvMetaStmt::PROP_NS_NAME . 'home' => 'home',
        XhvMetaStmt::PROP_NS_NAME . 'up' => 'up',
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
            ? [ 'property' => $stmt->getPropCurie() ]
            : [];

        switch ($uri) {
            case DcFormat::PROP_URI:
                return null;

            case DcTitle::PROP_URI:
                return new Title((string)$stmt, $attrs);

            default:
                $metaName = static::PROP_URI_TO_META_NAME[$uri] ?? null;

                if (isset($metaName)) {
                    $attrs['name'] = $metaName;
                }

                /* Return a Meta obejct iff property or name are set. */
                if ($attrs) {
                    $attrs['content'] = (string)$stmt;

                    return new Meta($attrs);
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

        $attrs = [ 'href' => (string)$stmt->getObject(), 'rel' => $rel ];

        $rdfaData = $node->getRdfaData();

        if (isset($rdfaData)) {
            if (isset($rdfaData['dc:format'])) {
                $attrs['type'] = (string)$rdfaData['dc:format']->first();
            }

            if (isset($rdfaData['dc:language'])) {
                $attrs['hreflang'] = (string)$rdfaData['dc:language']->first();
            }

            if (isset($rdfaData['dc:title'])) {
                $attrs['title'] = (string)$rdfaData['dc:title']->first();
            }
        }

        return new Link(null, $attrs);
    }

    /** @param $stmt Statement whose object is a Node. */
    public function stmt2A(StmtInterface $stmt): A
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

        $node = $stmt->getObject();

        $attrs = [ 'href' => $node->getUri() ];

        if (isset($rel)) {
            $attrs['rel'] = $rel;
        }

        $rdfaData = $node->getRdfaData();

        if (isset($rdfaData)) {
            if (isset($rdfaData['dc:format'])) {
                $attrs['type'] = (string)$rdfaData['dc:format']->first();
            }

            if (isset($rdfaData['dc:language'])) {
                $attrs['hreflang'] = (string)$rdfaData['dc:language']->first();
            }

            if (isset($rdfaData['dc:title'])) {
                $title = (string)$rdfaData['dc:title']->first();
            }
        }

        return new A($title ?? ucfirst($stmt->getPropLocalName()), $attrs);
    }

    public function rdfaData2Html(RdfaData $rdfaData): HtmlNodes
    {
        $htmlNodes = [];

        foreach ($rdfaData as $stmts) {
            foreach ($stmts as $stmt) {
                $element = $stmt->getObject() instanceof Node
                    ? $this->stmt2Link($stmt)
                    : $this->stmt2Meta($stmt);

                if (isset($element)) {
                    $htmlNodes[] = $element;
                }
            }
        }

        return new HtmlNodes($htmlNodes);
    }

    public function rdfaData2NsAttrs(RdfaData $rdfaData): array
    {
        $attrs = [];

        foreach ($rdfaData->createNamespaceMap() as $prefix => $nsName) {
            /* Do not include proprietary namespaces into HTML. */
            if (substr($nsName, 0, 4) != 'tag:') {
                $attrs["xmlns:$prefix"] = $nsName;
            }
        }

        return $attrs;
    }
}
