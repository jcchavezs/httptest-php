<?php

namespace HttpTest;

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Http\Response;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use RuntimeException;

class HttpTestServer
{
    const HOST = 'localhost';

    const MAX_NUMBER_OF_RETRIES = 10;

    const SWITCH_CHECKER_INTERVAL = 0.1;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var callable
     */
    private $handler;

    /**
     * @var int
     */
    private $port;

    /**
     * @var ServerSwitch
     */
    private $switch;

    private function __construct($handler, LoopInterface $loop, $port, $key)
    {
        $this->handler = $handler;
        $this->loop = $loop;
        $this->port = $port;
        $this->switch = $key;
    }

    /**
     * @param callable $handler
     * @return HttpTestServer
     */
    public static function create($handler)
    {
        $serverSwitch = ServerSwitch::create();

        $port = self::findAvailablePort();

        $loop = Factory::create();
        $loop->addPeriodicTimer(self::SWITCH_CHECKER_INTERVAL, function () use (&$loop, $serverSwitch) {
            if ($serverSwitch->isOff()) {
                $loop->stop();
                $serverSwitch->reset();
            }
        });

        return new self($handler, $loop, $port, $serverSwitch);
    }

    /**
     * Starts the server
     *
     * @return void
     * @throws ServerCouldNotBeLaunched
     */
    public function start()
    {
        $handler = $this->handler;

        $server = new HttpServer(function (ServerRequestInterface $request) use ($handler) {
            $response = new Response();
            $handler($request, $response);
            return $response;
        });

        $socket = new SocketServer($this->port, $this->loop);
        $server->listen($socket);

        $pid = pcntl_fork();
        if ($pid === -1) {
            ServerCouldNotBeLaunched::forFailedForking();
        } elseif ($pid) {
            $this->loop->run();

            pcntl_wait($status);

            exit(0);
        } else {
            $this->waitForReady();
        }
    }

    /**
     * Stops the server
     *
     * @return void
     */
    public function stop()
    {
        $this->switch->off();
    }

    /**
     * Returns the server url
     *
     * @return string
     */
    public function getUrl()
    {
        return sprintf('http://%s:%d', self::HOST, $this->port);
    }

    /**
     * Waits for the server to be ready
     *
     * @return void
     * @throws ServerCouldNotBeLaunched
     */
    private function waitForReady()
    {
        $retries = 1;
        $error = true;

        while ($error) {
            if ($retries === self::MAX_NUMBER_OF_RETRIES) {
                ServerCouldNotBeLaunched::forMaxAttempts($retries);
            }

            usleep(200 * $retries);

            if ($fp = @fsockopen(self::HOST, $this->port, $errorCode, $errorMessage, 1)) {
                fclose($fp);
                break;
            }

            $retries++;
        }
    }

    private static function findAvailablePort()
    {
        for ($port = 60000; $port < 65535; $port++) {
            if (@fsockopen(self::HOST, $port) === false) {
                break;
            }
        }
        return $port;
    }
}
