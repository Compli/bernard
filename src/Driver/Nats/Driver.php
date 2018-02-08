<?php

namespace Bernard\Driver\Nats;

final class Driver implements \Bernard\Driver
{
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
        // TODO: Implement countMessages() method.
    }

    /**
     * Insert a message at the top of the queue.
     *
     * @param string $queueName
     * @param string $message
     */
    public function pushMessage($queueName, $message)
    {
        // TODO: Implement pushMessage() method.
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
        // TODO: Implement popMessage() method.
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