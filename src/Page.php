<?php

namespace alcamo\html_page;

use Laminas\Diactoros\Stream;
use alcamo\http\Response;

/**
 * @namespace alcamo::html_page
 *
 * @brief Modular factory for HTML pages
 */

/**
 * @brief HTML page consisting of an HTML factory and a PSR7 message body
 * stream
 *
 * @date Last reviewed 2021-06-24
 */
class Page
{
    private $htmlFactory_; ///< Factory
    private $body_;        ///< Stream
    private $statusCode_;  ///< integer

    public function __construct(?Factory $htmlFactory = null)
    {
        /** If $factory is not given, create an insatnce of Factory. */
        $this->htmlFactory_ = $htmlFactory ?? new Factory();

        $this->body_ = new Stream('php://memory', 'wb+');

        /** Intialize the status code to 200, may be changed later by
         *  setStatusCode(). */
        $this->statusCode_ = 200;
    }

    public function getHtmlFactory(): Factory
    {
        return $this->htmlFactory_;
    }

    public function getBody(): Stream
    {
        return $this->body_;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode_;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode_ = $statusCode;
    }

    /// Write data to the message body stream
    public function write(?string $htmlData): int
    {
        return $this->body_->write((string)$htmlData);
    }

    /// Create a response
    public function createResponse(): Response
    {
        return new Response(
            $this->htmlFactory_->getRdfaData(),
            $this->body_,
            $this->statusCode_
        );
    }

    /**
     * @brief Start the page
     *
     * Write result of Factory::createBegin() to the body stream.
     */
    public function begin(
        ?iterable $resources = null,
        ?Nodes $extraHeadNodes = null,
        ?array $bodyAttrs = null
    ): void {
        $this->body_->write(
            $this->htmlFactory_['page']
                ->createBegin($resources, $extraHeadNodes, $bodyAttrs)
        );
    }

    /**
     * @brief Finalize the page
     *
     * Write result of Factory::createEnd() to the body stream and emit the
     * response unless $delayEmission isn true
     */
    public function end(?bool $delayEmission = null): void
    {
        $this->body_->write($this->htmlFactory_['page']->createEnd());

        if (!$delayEmission) {
            $this->createResponse()->emit();
        }
    }
}
