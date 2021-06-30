<?php

namespace alcamo\html_page;

use PHPUnit\Framework\TestCase;
use alcamo\exception\FileNotFound;
use alcamo\http\Response;

class ErrorPageTest extends TestCase
{
    /**
     * @dataProvider createStatusCodeFromThrowableProvider
     */
    public function testCreateStatusCodeFromThrowable($e, $expectedStatusCode)
    {
        $this->assertSame(
            $expectedStatusCode,
            ErrorPage::createStatusCodeFromThrowable($e)
        );
    }

    public function createStatusCodeFromThrowableProvider()
    {
        return [
            'FileNotFound' => [ new FileNotFound('foo.txt'), 404 ],
            'other'        => [ new \Exception(), 500 ]
        ];
    }

    /**
     * @dataProvider showThrowableProvider
     */
    public function testShowThrowable(
        $e,
        $displayErrors,
        $expectedResultClass,
        $expectedStatusCode
    ) {
        ini_set('display_errors', $displayErrors);

        $result = ErrorPage::showThrowable($e, null, true);

        $this->assertInstanceOf($expectedResultClass, $result);

        $this->assertSame(
            $expectedStatusCode,
            $result->getStatusCode()
        );

        if ($result instanceof ErrorPage) {
            $this->assertStringContainsString(
                get_class($e),
                $result->getBody()
            );
        } else {
            $this->assertStringContainsString(
                $result->getReasonPhrase(),
                $result->getBody()
            );
        }
    }

    public function showThrowableProvider()
    {
        return [
            'FileNotFound-short' => [
                new FileNotFound('foo.txt'),
                0,
                Response::class,
                404
            ],
            'FileNotFound-long' => [
                new FileNotFound('foo.txt'),
                1,
                ErrorPage::class,
                404
            ],
            'other-short' => [
                new \Exception(),
                0,
                Response::class,
                500
            ],
            'other-long' => [
                new \Exception(),
                1,
                ErrorPage::class,
                500
            ]
        ];
    }
}
