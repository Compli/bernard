<?php

namespace Bernard\Driver\Nats;

use Closure;
use NatsStreaming\Connection;
use NatsStreaming\Msg;
use NatsStreaming\Subscription;
use NatsStreaming\SubscriptionOptions;
use NatsStreamingProtos\Ack;
use NatsStreamingProtos\StartPosition;

class Driver implements \Bernard\Driver
{
    /** @var Connection */
    protected $connection;
    protected $counts = [];
    protected $poppedMessage = [];
    protected $hasManualAcknowledgement;
    protected $subscriptionOptions;
    /** @var Subscription[] */
    protected $subscriptions;

    public function __construct(Connection $connection, $options = [])
    {
        $this->connection = $connection;
        $this->connection->connect();
        $subscriptionOptions = array_merge($options, ['startAt' => StartPosition::NewOnly()]);
        $this->subscriptionOptions = new SubscriptionOptions($subscriptionOptions);
        $getManualAcknowledgement = function(SubscriptionOptions $subscriptionOptions) {
            $closure = function() {
                 return $this->manualAck;
            };

            return Closure::bind($closure, $subscriptionOptions, SubscriptionOptions::class)->__invoke();
        };
        $this->hasManualAcknowledgement = $getManualAcknowledgement($this->subscriptionOptions);
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
        return isset($this->counts[$queueName]) ? $this->counts[$queueName] : 0;
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
        $this->increaseQueueCount($queueName);
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
        $this->poppedMessage = [null, null];

        $handle = function (Msg $message) use ($queueName) {
            $content = $message->getData()->getContents();
            $this->poppedMessage = [$content, $message->getSequence()];
            if (! $this->hasManualAcknowledgement) {
                $this->decreaseQueueCount($queueName);
            }
        };
        if (empty($this->subscriptions[$queueName])) {
            $this->subscriptions[$queueName] = $this->connection->subscribe($queueName, $handle, $this->subscriptionOptions);
        }
        $this->connection->natsCon()->setStreamTimeout($duration);
        $this->subscriptions[$queueName]->wait(1);

        return $this->poppedMessage;
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
        $req = new Ack();
        $req->setSubject($queueName);
        $req->setSequence($receipt);
        $data = $req->toStream()->getContents();
        $this->connection->natsCon()->publish($this->subscriptions[$queueName]->getAckInbox(), $data);
        $this->decreaseQueueCount($queueName);
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
        $info = [];
        $streamSocket = $this->connection->natsCon()->getStreamSocket();
        if (!$streamSocket) {
            return [];
        }
        $info['stream_info'] = stream_get_meta_data($streamSocket);

        return $info;
    }

    protected function decreaseQueueCount($queueName)
    {
        if (!isset($this->counts[$queueName])) {
            $this->counts[$queueName] = 0;
        }
        $this->counts[$queueName]--;

        if (0 > $this->counts[$queueName]) {
            $this->counts[$queueName] = 0;
        }
    }

    protected function increaseQueueCount($queueName)
    {
        if (!isset($this->counts[$queueName])) {
            $this->counts[$queueName] = 0;
        }
        $this->counts[$queueName]++;
    }
}
