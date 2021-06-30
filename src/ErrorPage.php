<?php

namespace alcamo\html_page;

use alcamo\exception\FileNotFound;
use alcamo\http\Response;

/**
 * @brief HTML page used to display an error
 *
 * @date Last reviewed 2021-06-24
 */
class ErrorPage extends Page
{
    /// Return a status code depending on the type of a throwable
    public static function createStatusCodeFromThrowable(\Throwable $e)
    {
        switch (true) {
            case $e instanceof FileNotFound:
                return 404;

            default:
                return 500;
        }
    }

    /**
     * @brief Shown an error page derived from a throwable
     *
     * If the configuration option `display_errors` is set, return an
     * ErrorPage object with the exception details in html format. Otherwise,
     * return a Response object with the default reason phrase as text
     * content.
     */
    public static function showThrowable(
        \Throwable $e,
        ?Factory $htmlFactory = null,
        ?bool $delayEmission = null
    ) {
        $statusCode = static::createStatusCodeFromThrowable($e);

        if (ini_get('display_errors')) {
            $page = new static($htmlFactory);

            $page->setStatusCode($statusCode);

            $page->begin();
            $page->write($page->getHtmlFactory()->renderThrowable($e));
            $page->end($delayEmission);

            return $page;
        } else {
            $response = Response::newFromStatusAndText($statusCode);

            if (!$delayEmission) {
                $response->emit();
            }

            return $response;
        }
    }
}
