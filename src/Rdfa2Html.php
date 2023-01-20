<?php

namespace alcamo\html_page;

use alcamo\rdfa\{
    DcAbstract,
    DcCreator,
    DcSource,
    Node,
    RdfaData,
    RelContents,
    RelHome,
    RelUp,
    StmtInterface
};
use alcamo\html_creation\Link;

class Rdfa2html
{
    public const PROP_URI2META_NAME = [
        DcCreator::PROP_URI  => 'author',
        DcAbstract::PROP_URI => 'description'
    ];

    public const PROP_URI2HTML_REL = [
        DcCreator::PROP_URI   => 'author',
        DcSource::PROP_URI    => 'canonical',
        RelContents::PROP_URI => 'contents',
        RelHome::PROP_URI     => 'home',
        RelUp::PROP_URI       => 'up'
    ];

    public function stmt2Meta(StmtInterface $stmt): ?Meta
    {
        $uri = $stmt->getPropUri();

        $metaName = static::PROP_URI2META_NAME[$uri] ?? null;

        if (!isset($metaName)) {
            return null;
        }

        /** todo special case dc:title */
    }

    public function node2LinkAttrs(Node $node): array
    {
        $attrs = [ 'href' => $node->getUri() ];

        $rdfaData = $node->getRdfaData();

        if (isset($rdfaData)) {
            if (isset($rdfaData['dc:format'])) {
                $attrs['type'] = $rdfaData['dc:format'];
            }

            if (isset($rdfaData['dc:language'])) {
                $attrs['hreflang'] = $rdfaData['dc:language'];
            }

            if (isset($rdfaData['dc:title'])) {
                $attrs['title'] = $rdfaData['dc:title'];
            }
        }

        return $attrs;
    }

    public function stmt2Link(StmtInterface $stmt): ?Link
    {
        $attrs = $this->node2LinkAttrs($stmt->getObject());

        $uri = $stmt->getPropUri();

        // do not include proprietary rel values into HTML code
        if (substr($uri, 0, 4) != 'tag:') {
            $rel = $stmt->getPropCurie();
        }

        $htmlRel = static::PROP_URI2HTML_REL[$uri] ?? null;

        if (isset($htmlRel)) {
            $rel = isset($rel) ? "$rel $htmlRel" : $htmlRel;
        }

        if (isset($rel)) {
            $attrs['rel'] = $rel;

            return new Link(null, $attrs);
        }

        return null;
    }

    public function rdfaData2Html(RdfaData $rdfaData): Nodes
    {
        /** todo, choose stmt2Meta() or stmt2Link() depending whether object
         *  is Node or not */
    }
}
