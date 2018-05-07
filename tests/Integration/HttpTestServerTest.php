<?php

namespace HttpTest\Tests\Integration;

use HttpTest\HttpTestServer;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpTestServerTest extends PHPUnit_Framework_TestCase
{
    const TEST_BODY = 'test_body';
    const TEST_SUCCESS_STATUS_CODE = 202;
    const TEST_ERROR_STATUS_CODE = 500;

    public function testHttpSuccess()
    {
        $t = $this;

        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) use ($t) {
                $t->assertEquals('POST', $request->getMethod());
                $t->assertEquals('application/json', $request->getHeader('Content-Type')[0]);
                $t->assertEquals(self::TEST_BODY, (string) $request->getBody());
                $response = $response->withStatus(self::TEST_SUCCESS_STATUS_CODE);
            }
        );

        $pid = pcntl_fork();
        if ($pid === -1) {
            $this->fail('Error forking thread.');
        } elseif ($pid) {
            $server->start();

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

                $this->assertEquals(self::TEST_SUCCESS_STATUS_CODE, $statusCode);
            } else {
                $server->stop();

                $this->fail(curl_error($handle));
            }

            $server->stop();

            exit;
        }
    }

    public function testHttpFails()
    {
        $t = $this;

        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) use ($t) {
                $t->assertEquals('POST', $request->getMethod());
                $t->assertEquals('application/json', $request->getHeader('Content-Type')[0]);
                $t->assertEquals(self::TEST_BODY, (string) $request->getBody());
                $response = $response->withStatus(self::TEST_ERROR_STATUS_CODE);
            }
        );

        $pid = pcntl_fork();
        if ($pid === -1) {
            $this->fail('Error forking thread.');
        } elseif ($pid) {
            $server->start();

            pcntl_wait($status);
        } else {
            $server->waitForReady();

            $handle = curl_init($server->getUrl());
            curl_setopt($handle, CURLOPT_POST, 1);
            curl_setopt($handle, CURLOPT_FAILONERROR, 1);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);

            if (curl_exec($handle) !== false) {
                $server->stop();
                $this->fail('Unexpected success.');
            }

            $server->stop();

            exit;
        }
    }
}
