<?php

namespace WyriHaximus\React\Tests\ChildProcess\Messenger;

use Phake;
use React\EventLoop\Factory as EventLoopFactory;
use React\Promise\Deferred;
use React\Stream\Stream;
use WyriHaximus\React\ChildProcess\Messenger\Factory;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Line;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;

class MessengerTest extends \PHPUnit_Framework_TestCase
{

    public function testSetAndHasRpc()
    {
        $loop = EventLoopFactory::create();
        $messenger = Factory::child($loop);

        $payload = [
            'a',
            'b',
            'c',
        ];
        $callableFired = false;
        $callable = function (array $passedPayload) use (&$callableFired, $payload) {
            $this->assertEquals($payload, $passedPayload);
            $callableFired = true;
        };

        $messenger->registerRpc('test', $callable);
        $this->assertFalse($messenger->hasRpc('tset'));
        $this->assertTrue($messenger->hasRpc('test'));

        $messenger->callRpc('test', $payload);

        $this->assertTrue($callableFired);
    }

    public function testGetters()
    {
        $loop = \React\EventLoop\Factory::create();
        $stdin = new Stream(STDIN, $loop);
        $stdout = new Stream(STDOUT, $loop);
        $stderr = new Stream(STDERR, $loop);

        $messenger = new Messenger($stdin, $stdout, $stderr, []);

        $this->assertSame($stdin, $messenger->getStdin());
        $this->assertSame($stdout, $messenger->getStdout());
        $this->assertSame($stderr, $messenger->getStderr());
    }

    public function testMessage()
    {
        $loop = \React\EventLoop\Factory::create();
        $stdin = Phake::mock(Stream::class);
        $stdout = new Stream(STDOUT, $loop);
        $stderr = new Stream(STDERR, $loop);

        $messenger = new Messenger($stdin, $stdout, $stderr, [
            'write' => 'stdin',
        ]);

        $messenger->message(\WyriHaximus\React\ChildProcess\Messenger\Messages\Factory::message([
            'foo' => 'bar',
        ]));

        Phake::verify($stdin)->write($this->isType('string'));
    }

    public function testRpc()
    {
        $loop = \React\EventLoop\Factory::create();
        $stdin = Phake::mock(Stream::class);
        $stdout = new Stream(STDOUT, $loop);
        $stderr = new Stream(STDERR, $loop);

        $messenger = new Messenger($stdin, $stdout, $stderr, [
            'write' => 'stdin',
        ]);

        $messenger->rpc(\WyriHaximus\React\ChildProcess\Messenger\Messages\Factory::rpc('target', [
            'foo' => 'bar',
        ]));

        Phake::verify($stdin)->write($this->isType('string'));
    }

    public function testOnData()
    {

        $loop = \React\EventLoop\Factory::create();
        $stdin = Phake::mock(Stream::class);

        Phake::when($stdin)->on('data', $this->isType('callable'))->thenGetReturnByLambda(function ($target, $callback) {
            $callback((string)new Line(\WyriHaximus\React\ChildProcess\Messenger\Messages\Factory::message([]), []));
        });

        $stdout = new Stream(STDOUT, $loop);
        $stderr = new Stream(STDERR, $loop);

        $messenger = new Messenger($stdin, $stdout, $stderr, [
            'read' => 'stdin',
        ]);
    }
}
