<?php

namespace Bernard\Tests\Driver;

use Bernard\Driver\Nats\Driver;
use Nats\ConnectionOptions as NatsOptions;
use NatsStreaming\Connection;
use NatsStreaming\ConnectionOptions;

class NatsDriverFunctionalTest extends \PHPUnit\Framework\TestCase
{
    /** @var Connection */
    private $connection;
    /** @var Driver */
    private $driver;
    private $messages;

    public function setUp()
    {
        $natsOptions = new NatsOptions([
            'host' => 'localhost',
            'port' => '4222',
        ]);
        $options = new ConnectionOptions();
        $options->setNatsOptions($natsOptions);
        $options->setClientID("test");
        $this->connection = new Connection($options);
        $this->driver = new Driver($this->connection);
        return;
        $subscriptionOptions = [
            'ackWaitSecs' => 3,
            'durableName' => 'test',
            'manualAck' => true,
        ];
        $this->driver = new Driver($this->connection, $subscriptionOptions);
    }

    public function tearDown()
    {

    }

    public function testPopMessageDurationWithNoNewMessages()
    {
        $runtime = microtime(true) + 2; // allow extra time for processing
        $message = $this->driver->popMessage('foo', 1);

        $this->driver->acknowledgeMessage('foo', $message[1]);

        $this->assertEmpty($message[0]);
        $this->assertEmpty($message[1]);
        $this->assertLessThan($runtime, microtime(true));
    }

    public function testCountMessageWithNonExistentQueue()
    {
        $this->assertEquals(0, $this->driver->countMessages('ThisQueueDoesNotExist'));
    }

    /**
     * @medium
     * @covers ::acknowledgeMessage()
     * @covers ::countMessages()
     * @covers ::popMessage()
     * @covers ::pushMessage()
     */
    public function testMessageLifecycle()
    {
        $this->assertEquals(0, $this->driver->countMessages('foo'));

        $this->driver->pushMessage('foo', 'message1');
        $this->assertEquals(1, $this->driver->countMessages('foo'));

        $this->driver->pushMessage('foo', 'message2');
        $this->assertEquals(2, $this->driver->countMessages('foo'));

        list($message1, $receipt1) = $this->driver->popMessage('foo');
        $this->assertSame('message1', $message1, 'The first message pushed is popped first');
        $this->assertInternalType('int', $receipt1, 'The message receipt is the sequence number');
        $this->assertEquals(2, $this->driver->countMessages('foo'), 'The message has not been acknowledged so it should be there');

        list($message2, $receipt2) = $this->driver->popMessage('foo');
        $this->assertSame('message2', $message2, 'The second message pushed is popped second');

        list($message3, $receipt3) = $this->driver->popMessage('foo', 1);
        $this->assertNull($message3, 'Null message is returned when popping an empty queue');
        $this->assertNull($receipt3, 'Null receipt is returned when popping an empty queue');

        sleep(4); // duration before reinserting

        list($message1a, $receipt1a) = $this->driver->popMessage('foo');

        $this->assertEquals(2, $this->messages->count(), 'Popped messages remain in the database');

        $this->driver->acknowledgeMessage('foo', $receipt1);
        $this->assertEquals(1, $this->messages->count(), 'Acknowledged messages decrease the count');

        $this->driver->acknowledgeMessage('foo', $receipt2);

        list($message3, $receipt3) = $this->driver->popMessage('foo', 1);
        $this->assertNull($message3, 'The queue should not be returning a message now that the messages have been acknowledged');
        $this->assertNull($receipt3);
    }

    public function testInfo()
    {
        $info = $this->driver->info();

        $this->assertArrayHasKey('stream_info', $info);
    }
}
