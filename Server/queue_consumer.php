<?php

include "database.php";
include "exec.php";

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

$exchange = 'router';
$queue = 'msgs';
$consumerTag = 'consumer';

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

/**
 * @param \PhpAmqpLib\Message\AMQPMessage $message
 */
function process_message($message)
{

    $messageBody = json_decode($message->body, true);

    if ($messageBody["machine_id"] == "*")
        command_exec_all($messageBody["params"]);
    else {

        command_exec_machine($messageBody["params"], $messageBody["machine_id"]);
    }

    $message->ack();

    // Send a message with the string "quit" to cancel the consumer.
    if ($message->body === 'quit') {
        $message->getChannel()->basic_cancel($message->getConsumerTag());
    }
}

function command_exec_all($params)
{
    $params = json_decode($params, true);
    $machines = select(array("*"), "machines");
    foreach ($machines as $temp_machine) {
        $machine = get_object_vars($temp_machine);
        $machine_result = array("status" => 0, "machine_id" => $machine["id"]);
        $output = "";
        $error = "";
        if (!executeCmdOnSSH($machine["host"], $machine["username"], $machine["password"], $params["command"], $output, $error))
            $machine_result["error"] = $error;
        else {
            if ($error == "")
                $machine_result["status"] = 1;
            else
                $machine_result["error"] = $error;
            $machine_result["output"] = $output;
        }

        $query_params = array(
            "machine_id" => $machine_result["machine_id"],
            "command" => $params["command"],
            "runned_by" => (isset($params["runned_by"]) && $params["runned_by"] == "cron") ? "cron" : "manual",
            "status" => $machine_result["status"],
            "output" => isset($machine_result["output"]) ? $machine_result["output"] : "",
            "error" => isset($machine_result["error"]) ? $machine_result["error"] : "",
        );

        $command_id = insert("command_history", $query_params);
        insert("job_command", array("job_id" => $params["job_id"], "command_id" => $command_id));
    }
    update("jobs", array("status" => 1), array("id" => $params["job_id"]));
}

function command_exec_machine($params, $machine_id)
{
    $params = json_decode($params, true);

    $machine = select(array("*"), "machines", array("id" => $machine_id));
    $machine = get_object_vars($machine[0]);

    $output = "";
    $error = "";
    if (!executeCmdOnSSH($machine["host"], $machine["username"], $machine["password"], $params["command"], $output, $error))
        $command["error"] = $error;
    else {
        if ($error == "")
            $payload["status"] = 1;
        else
            $command["error"] = $error;
        $command["output"] = $output;
    }

    $query_params = array(
        "machine_id" => $machine_id,
        "command" => $params["command"],
        "runned_by" => (isset($params["runned_by"]) && $params["runned_by"] == "cron") ? "cron" : "manual",
        "status" => $payload["status"],
        "output" => isset($command["output"]) ? $command["output"] : "",
        "error" => isset($command["error"]) ? $command["error"] : "",
    );

    $command_id = insert("command_history", $query_params);
    insert("job_command", array("job_id" => $params["job_id"], "command_id" => $command_id));
    update("jobs", array("status" => 1), array("id" => $params["job_id"]));
}

/*
    queue: Queue from where to get the messages
    consumer_tag: Consumer identifier
    no_local: Don't receive messages published by this consumer.
    no_ack: If set to true, automatic acknowledgement mode will be used by this consumer. See https://www.rabbitmq.com/confirms.html for details.
    exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
    nowait:
    callback: A PHP Callback
*/

$channel->basic_consume($queue, $consumerTag, false, false, false, false, 'process_message');

/**
 * @param \PhpAmqpLib\Channel\AMQPChannel $channel
 * @param \PhpAmqpLib\Connection\AbstractConnection $connection
 */
function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

// Loop as long as the channel has callbacks registered
$channel->consume();
