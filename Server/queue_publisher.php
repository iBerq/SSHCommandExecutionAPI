<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

function publish_job($params, $machine_id)
{
    $status = false;
    $exchange = 'router';
    $queue = 'msgs';

    $connection = new AMQPStreamConnection("localhost", 5672, "guest", "guest");
    $channel = $connection->channel();

    /*
    The following code is the same both in the consumer and the producer.
    In this way we are sure we always have a queue to consume from and an
    exchange where to publish messages.
    */

    /*
    name: $queue
    passive: false
    durable: true // the queue will survive server restarts
    exclusive: false // the queue can be accessed in other channels
    auto_delete: false //the queue won't be deleted once the channel is closed.
*/
    $channel->queue_declare($queue, false, true, false, false);

    /*
    name: $exchange
    type: direct
    passive: false
    durable: true // the exchange will survive server restarts
    auto_delete: false //the exchange won't be deleted once the channel is closed.
*/

    $channel->exchange_declare($exchange, "direct", false, true, false);

    $channel->queue_bind($queue, $exchange);

    $messageBody = array("params" => json_encode($params), "machine_id" => $machine_id);
    $message = new AMQPMessage(json_encode($messageBody), array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
    $channel->basic_publish($message, $exchange);

    $channel->close();
    $connection->close();
    $status = true;
    return $status;
}
