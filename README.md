# Http Test

[![Build Status](https://travis-ci.org/jcchavezs/httptest-php.svg?branch=master)](https://travis-ci.org/jcchavezs/httptest-php)
[![Latest Stable Version](https://poser.pugx.org/jcchavezs/httptest/v/stable)](https://packagist.org/packages/jcchavezs/httptest)
[![Total Downloads](https://poser.pugx.org/jcchavezs/httptest/downloads)](https://packagist.org/packages/jcchavezs/httptest)
[![License](https://poser.pugx.org/jcchavezs/httptest/license)](https://packagist.org/packages/jcchavezs/httptest)

Library for for HTTP integration tests.

HttpTest is strongly inspired on the [httptest go library](https://golang.org/pkg/net/http/httptest)

## Description

When testing functions that include HTTP calls, developers often create a wrapper class around `cURL`
functions and mock that class in order to unit test it. This technique unit tests the class but it is 
also important to test the actual HTTP call which requires an HTTP server listening to those calls. This
library provides such a server and allow developers to do assertions both in the client and server side.

## Installation

```bash
composer require --dev jcchavezs/httptest
```

## Example

Test a `cURL` HTTP request:

```php
<?php

namespace HttpTest\Tests\Integration;

use HttpTest\HttpTestServer;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TestServerTest extends PHPUnit_Framework_TestCase
{
    const TEST_BODY = 'test_body';
    const TEST_STATUS_CODE = 202;

    public function testHttpSuccess()
    {
        $t = $this;

        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) use ($t) {
                /* Assert the HTTP call includes the expected values */
                $t->assertEquals('POST', $request->getMethod());
                $t->assertEquals('application/json', $request->getHeader('Content-Type')[0]);
                $t->assertEquals(self::TEST_BODY, (string) $request->getBody());
                $response = $response->withStatus(self::TEST_STATUS_CODE);
            }
        );

        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->fail('Error forking thread.');
        } elseif ($pid) {
            // The fork allows to run the HTTP server in background.
            $server->start();
            
            // This prevents zombie child process
            pcntl_wait($status);
        } else {
            $server->waitForReady();

            $handle = curl_init($server->getUrl());
            curl_setopt($handle, CURLOPT_POST, 1);
            curl_setopt($handle, CURLOPT_POSTFIELDS, self::TEST_BODY);
            curl_setopt($handle, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(self::TEST_BODY),
            ]);

            if (curl_exec($handle) === true) {
                $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                curl_close($handle);

                // Assert client behaviour based on the server response
                $this->assertEquals(self::TEST_STATUS_CODE, $statusCode);
            } else {
                // Stop the server before as `$this->fail(...)` throws an exception
                // In a try/catch block, this should be in the finally block
                $server->stop();
                
                $this->fail(curl_error($handle));
            }
            
            $server->stop();
            
            exit;
        }
    }
}
```

## Tests

```bash
composer test
```
