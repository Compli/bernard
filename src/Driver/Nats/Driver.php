<?php

namespace Bernard\Driver\Nats;

use NatsStreaming\Connection;
use NatsStreaming\Msg;
use NatsStreaming\SubscriptionOptions;
use NatsStreamingProtos\StartPosition;

class Driver implements \Bernard\Driver
{
    /** @var Connection */
    protected $connection;
    protected $subscriptionOptions;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->connection->connect();
        $this->subscriptionOptions = new SubscriptionOptions([
            'durableName' => 'test',
            'startAt' => StartPosition::NewOnly(),
            'manualAck' => true,
        ]);

    }

    /**
     * Returns a list of all queue names.
     *
     * @return array
     */
    public function listQueues()
    {
        // TODO: Implement listQueues() method.
    }

    /**
     * Create a queue.
     *
     * @param string $queueName
     */
    public function createQueue($queueName)
    {
        // TODO: Implement createQueue() method.
    }

    /**
     * Count the number of messages in queue. This can be a approximately number.
     *
     * @param string $queueName
     *
     * @return int
     */
    public function countMessages($queueName)
    {
        return $this->connection->pubsCount();
    }

    /**
     * Insert a message at the top of the queue.
     *
     * @param string $queueName
     * @param string $message
     */
    public function pushMessage($queueName, $message)
    {
        $response = $this->connection->publish($queueName, $message);
    }

    /**
     * Remove the next message in line. And if no message is available
     * wait $duration seconds.
     *
     * @param string $queueName
     * @param int $duration
     *
     * @return array An array like array($message, $receipt);
     */
    public function popMessage($queueName, $duration = 5)
    {
        $poppedMessage = [null, null];

        $handle = function (Msg $message) use (&$poppedMessage) {
            $content = $message->getData()->getContents();
            $poppedMessage = [$content, $message->getSequence()];
        };
        $subscription = $this->connection->subscribe($queueName, $handle, $this->subscriptionOptions);
        $this->connection->natsCon()->setStreamTimeout($duration);

        $subscription->wait(1);

        return $poppedMessage;
    }

    /**
     * If the driver supports it, this will be called when a message
     * have been consumed.
     *
     * @param string $queueName
     * @param mixed $receipt
     */
    public function acknowledgeMessage($queueName, $receipt)
    {
        // TODO: Implement acknowledgeMessage() method.
    }

    /**
     * Returns a $limit numbers of messages without removing them
     * from the queue.
     *
     * @param string $queueName
     * @param int $index
     * @param int $limit
     */
    public function peekQueue($queueName, $index = 0, $limit = 20)
    {
        // TODO: Implement peekQueue() method.
    }

    /**
     * Removes the queue.
     *
     * @param string $queueName
     */
    public function removeQueue($queueName)
    {
        // TODO: Implement removeQueue() method.
    }

    /**
     * @return array
     */
    public function info()
    {
        // TODO: Implement info() method.
    }
}