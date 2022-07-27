<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;

include "exec.php";
include "database.php";
include "queue_publisher.php";

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

// Add Slim routing middleware
$app->addRoutingMiddleware();

// Set the base path to run the app in a subdirectory.
// This path is used in urlFor().
$app->add(new BasePathMiddleware($app));

$app->addErrorMiddleware(true, true, true);

// Define app routes
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("<!doctype html>");
    $response->getBody()->write("<html>");

    $response->getBody()->write("<head>");
    $response->getBody()->write("<title>SSH Command Execution API</title>");
    $response->getBody()->write("</head>");

    $response->getBody()->write("<body>");
    $response->getBody()->write("<h1>SSH Command Execution API</h1>");
    $response->getBody()->write("<p>SSH Command Execution API allows you to send commands to and get the responses from saved remote machines in an efficiently manner.</p>");
    $response->getBody()->write("<p>For more information, visit the <a href='/help'>help</a> page.</p>");
    $response->getBody()->write("</body>");

    $response->getBody()->write("</html>");
    return $response;
})->setName('root');

$app->get('/help', function (Request $request, Response $response) {
    $response->getBody()->write("<!doctype html>");
    $response->getBody()->write("<html>");

    $response->getBody()->write("<head>");
    $response->getBody()->write("<title>SSH Command Execution API Help Page</title>");
    $response->getBody()->write("</head>");

    $response->getBody()->write("<body>");
    $response->getBody()->write("<h1 style='border-bottom-style: solid; border-width: 5px;'>SSH Command Execution API Help Page</h1>");
    $response->getBody()->write("<br>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h2 style='color: gray; border-bottom-style: solid; border-color: gray; border-width: 3px;'>/machines</h2>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: green; display: inline;'>GET</h3><h3 style='padding-left: 30px; display: inline;'>/machines/list</h3>");
    $response->getBody()->write("<p>Lists all of the saved machines.</p>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "machines": [
        {
            "id": 6,
            "machine_name": "test",
            "host": "client",
            "username": "client",
            "password": "123456"
        },
        {
            "id": 7,
            "machine_name": "test2",
            "host": "client2",
            "username": "client2",
            "password": "123456"
        },
        {
            "id": 8,
            "machine_name": "test3",
            "host": "client3",
            "username": "client3",
            "password": "123456"
        }
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: gold; display: inline;'>POST</h3><h3 style='padding-left: 30px; display: inline;'>/machines/add</h3>");
    $response->getBody()->write("<p>Save specified machine.</p>");
    $response->getBody()->write("<h4>Body</h4>");
    $response->getBody()->write("
    <table style='border: 1px solid black; border-collapse: collapse;'> 
        <thead>
            <tr>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Key</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Value</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>machine_name</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>test</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>custom name of the machine</td>
            </tr>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>host</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>client</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>host name or ip of the machine</td>
            </tr>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>username</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>client</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>username of the host machine</td>
            </tr>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>machine_name</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>123456</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>password of the host machine</td>
            </tr>
        </tbody>
    </table>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "machine": [
        {
            "id": 3,
            "machine_name": "test",
            "host": "client",
            "username": "client",
            "password": "123456"
        }
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: gold; display: inline;'>POST</h3><h3 style='padding-left: 30px; display: inline;'>/machines/delete</h3>");
    $response->getBody()->write("<p>Delete specified machine.</p>");
    $response->getBody()->write("<h4>Body</h4>");
    $response->getBody()->write("
    <table style='border: 1px solid black; border-collapse: collapse;'> 
        <thead>
            <tr>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Key</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Value</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>id</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>3</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>unique identifier of the machine</td>
            </tr>
        </tbody>
    </table>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "machine": [
        {
            "id": 3,
            "machine_name": "test",
            "host": "client",
            "username": "client",
            "password": "123456"
        }
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h2 style='color: gray; border-bottom-style: solid; border-color: gray; border-width: 3px;'>/command</h2>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: gold; display: inline;'>POST</h3><h3 style='padding-left: 30px; display: inline;'>/command/exec/{machine_name}</h3>");
    $response->getBody()->write("<p>Execute given command at specified machine.</p>");
    $response->getBody()->write("<h4>Body</h4>");
    $response->getBody()->write("
    <table style='border: 1px solid black; border-collapse: collapse;'> 
        <thead>
            <tr>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Key</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Value</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>command</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>date</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>command that will run on the {machine_name}</td>
            </tr>
        </tbody>
    </table>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "result": [
        {
            "id": 1,
            "date": "2022-07-27 10:31:54",
            "machine_name": "client",
            "command": "date",
            "runned_by": "manual",
            "status": 1,
            "output": "Wed Jul 27 10:31:54 UTC 2022\n",
            "error": ""
        },
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: gold; display: inline;'>POST</h3><h3 style='padding-left: 30px; display: inline;'>/command/exec</h3>");
    $response->getBody()->write("<p>Execute given command at all of the saved machines.</p>");
    $response->getBody()->write("<h4>Body</h4>");
    $response->getBody()->write("
    <table style='border: 1px solid black; border-collapse: collapse;'> 
        <thead>
            <tr>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Key</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Value</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>command</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>date</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>command that will run on all saved machines</td>
            </tr>
        </tbody>
    </table>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "result": [
        [
            {
                "id": 2,
                "date": "2022-07-27 10:32:08",
                "machine_name": "client",
                "command": "date",
                "runned_by": "manual",
                "status": 1,
                "output": "Wed Jul 27 10:32:08 UTC 2022\n",
                "error": ""
            }
        ],
        [
            {
                "id": 3,
                "date": "2022-07-27 10:32:08",
                "machine_name": "client2",
                "command": "date",
                "runned_by": "manual",
                "status": 1,
                "output": "Wed Jul 27 10:32:08 UTC 2022\n",
                "error": ""
            }
        ],
        [
            {
                "id": 4,
                "date": "2022-07-27 10:32:08",
                "machine_name": "client3",
                "command": "date",
                "runned_by": "manual",
                "status": 0,
                "output": "",
                "error": "Can\'t connect to server."
            }
        ]
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: green; display: inline;'>GET</h3><h3 style='padding-left: 30px; display: inline;'>/command/history/{machine_name}</h3>");
    $response->getBody()->write("<p>Get command history of the specified machine.</p>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "machine_name": "client",
    "history": [
        {
            "id": 1,
            "date": "2022-07-27 10:31:54",
            "machine_name": "client",
            "command": "date",
            "runned_by": "manual",
            "status": 1,
            "output": "Wed Jul 27 10:31:54 UTC 2022\n",
            "error": ""
        },
        {
            "id": 2,
            "date": "2022-07-27 10:32:08",
            "machine_name": "client",
            "command": "date",
            "runned_by": "manual",
            "status": 1,
            "output": "Wed Jul 27 10:32:08 UTC 2022\n",
            "error": ""
        }
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: green; display: inline;'>GET</h3><h3 style='padding-left: 30px; display: inline;'>/command/history</h3>");
    $response->getBody()->write("<p>Get command history of all saved machines.</p>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "history": {
        "client": [
            {
                "id": 1,
                "date": "2022-07-27 10:31:54",
                "machine_name": "client",
                "command": "date",
                "runned_by": "manual",
                "status": 1,
                "output": "Wed Jul 27 10:31:54 UTC 2022\n",
                "error": ""
            },
            {
                "id": 2,
                "date": "2022-07-27 10:32:08",
                "machine_name": "client",
                "command": "date",
                "runned_by": "manual",
                "status": 1,
                "output": "Wed Jul 27 10:32:08 UTC 2022\n",
                "error": ""
            }
        ],
        "client2": [
            {
                "id": 3,
                "date": "2022-07-27 10:32:08",
                "machine_name": "client2",
                "command": "date",
                "runned_by": "manual",
                "status": 1,
                "output": "Wed Jul 27 10:32:08 UTC 2022\n",
                "error": ""
            }
        ],
        "client3": [
            {
                "id": 4,
                "date": "2022-07-27 10:32:08",
                "machine_name": "client3",
                "command": "date",
                "runned_by": "manual",
                "status": 0,
                "output": "",
                "error": "Can\'t connect to server."
            }
        ]
    }
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h2 style='color: gray; border-bottom-style: solid; border-color: gray; border-width: 3px;'>/cron</h2>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: green; display: inline;'>GET</h3><h3 style='padding-left: 30px; display: inline;'>/cron/list</h3>");
    $response->getBody()->write("<p>Lists all of the active cron jobs.</p>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "cron_jobs": [
        {
            "id": 1,
            "machine_name": "client",
            "schedule": "* * * * *",
            "command": "date",
            "last_runned_command_id": 13
        },
        {
            "id": 2,
            "machine_name": "*",
            "schedule": "* * * * *",
            "command": "free -m",
            "last_runned_command_id": 10
        }
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: gold; display: inline;'>POST</h3><h3 style='padding-left: 30px; display: inline;'>/cron/add/{machine_name}</h3>");
    $response->getBody()->write("<p>Save given cron job to the specified machine.</p>");
    $response->getBody()->write("<h4>Body</h4>");
    $response->getBody()->write("
    <table style='border: 1px solid black; border-collapse: collapse;'> 
        <thead>
            <tr>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Key</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Value</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>schedule</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>* * * * *</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>schedule of the command that will run</td>
            </tr>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>command</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>date</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>command that will run schedular on the {machine_name}</td>
            </tr>
        </tbody>
    </table>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "cron_job": [
        {
            "id": 1,
            "machine_name": "client",
            "schedule": "* * * * *",
            "command": "date",
            "last_runned_command_id": null
        }
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: gold; display: inline;'>POST</h3><h3 style='padding-left: 30px; display: inline;'>/cron/add</h3>");
    $response->getBody()->write("<p>Save given cron job to all saved machines.</p>");
    $response->getBody()->write("<h4>Body</h4>");
    $response->getBody()->write("
    <table style='border: 1px solid black; border-collapse: collapse;'> 
        <thead>
            <tr>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Key</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Value</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>schedule</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>* * * * *</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>schedule of the command that will run</td>
            </tr>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>command</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>date</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>command that will run schedular on all saved machines</td>
            </tr>
        </tbody>
    </table>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "cron_job": [
        {
            "id": 2,
            "machine_name": "*",
            "schedule": "* * * * *",
            "command": "free -m",
            "last_runned_command_id": null
        }
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("<div>");
    $response->getBody()->write("<h3 style='color: gold; display: inline;'>POST</h3><h3 style='padding-left: 30px; display: inline;'>/cron/delete</h3>");
    $response->getBody()->write("<p>Delete specified cron job.</p>");
    $response->getBody()->write("<h4>Body</h4>");
    $response->getBody()->write("
    <table style='border: 1px solid black; border-collapse: collapse;'> 
        <thead>
            <tr>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Key</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Value</th>
                <th style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>id</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>2</td>
                <td style='border: 1px solid black; border-collapse: collapse; padding: 10px;'>unique identifier of the cron job</td>
            </tr>
        </tbody>
    </table>");
    $response->getBody()->write("<h4>Response</h4>");
    $response->getBody()->write('<pre style="border-style: solid; border-width: 1px; display: inline-block; padding: 10px;"><code>{
    "status": 1,
    "cron_job": [
        {
            "id": 2,
            "machine_name": "*",
            "schedule": "* * * * *",
            "command": "free -m",
            "last_runned_command_id": 15
        }
    ]
}</code></pre>');
    $response->getBody()->write("</div>");
    $response->getBody()->write("</div>");
    $response->getBody()->write("</body>");

    $response->getBody()->write("</html>");
    return $response;
});

######################################################################################################################
/*
        COMMAND
    */
######################################################################################################################

$app->post('/command/exec', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("status" => 0);
    if (!isset($params["command"])) {
        $payload["error"] = "No command is given.";
    } else {
        $job_id = insert("jobs", array("status" => 0));
        $params["job_id"] = $job_id;
        if (publish_job($params, "*")) {
            while (true) {
                $job = select_where(array("*"), "jobs", array("id" => $job_id));
                $job = get_object_vars($job[0]);
                if ($job["status"]) {
                    $payload["status"] = 1;
                    break;
                }
                sleep(1);
            }

            $command_list = select_where(array("*"), "job_command", array("job_id" => $job_id));
            $machine_result_array = array();
            foreach ($command_list as $command) {
                $command = get_object_vars($command);
                $command_id = $command["command_id"];
                array_push($machine_result_array, select_where(array("*"), "command_history", array("id" => $command_id)));
            }
            $payload["result"] = $machine_result_array;
        } else {
            $payload["error"] = "Server queue is not available.";
        }
    }
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/command/exec/{machine_id}', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("status" => 0, "machine_id" => $args["machine_id"]);
    if (!isset($params["command"])) {
        $payload["error"] = "No command is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!select_where(array("*"), "machines", array("id" => $args["machine_id"]))) {
        $payload["command"] = $params["command"];
        $payload["error"] = "Machine not found.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
    unset($payload["machine_id"]);

    $job_id = insert("jobs", array("status" => 0));
    $params["job_id"] = $job_id;

    if (publish_job($params, $args["machine_id"])) {
        while (true) {
            $job = select_where(array("*"), "jobs", array("id" => $job_id));
            $job = get_object_vars($job[0]);
            if ($job["status"]) {
                $payload["status"] = 1;
                break;
            }
            sleep(1);
        }

        $command = select_where(array("*"), "job_command", array("job_id" => $job_id));
        $command = get_object_vars($command[0]);
        $command_id = $command["command_id"];
        $payload["result"] = select_where(array("*"), "command_history", array("id" => $command_id));
    } else {
        $payload["error"] = "Server queue is not available.";
    }

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/command/history', function (Request $request, Response $response, $args) {
    $payload = array();
    $machines = select(array("*"), "machines");
    $machine_result_array = array();
    foreach ($machines as $temp_machine) {
        $machine = get_object_vars($temp_machine);
        $machine_result_array[$machine["id"]] = select_where(array("*"), "command_history", array("machine_id" => $machine["id"]));
    }
    $payload["history"] = $machine_result_array;
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/command/history/{machine_id}', function (Request $request, Response $response, $args) {
    $payload = array("status" => 0, "machine_id" => $args["machine_id"]);

    if (!select_where(array("*"), "machines", array("id" => $args["machine_id"])))
        $payload["error"] = "Machine not found.";
    else {
        $payload["status"] = 1;
        $payload["history"] = select_where(array("*"), "command_history", array("machine_id" => $args["machine_id"]));
    }

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

######################################################################################################################
/*
        MACHINE
    */
######################################################################################################################

$app->post('/machine/add', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("status" => 0);
    if (!isset($params["machine_name"])) {
        $payload["error"] = "No machine name is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!isset($params["host"])) {
        $payload["error"] = "No host is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!isset($params["username"])) {
        $payload["error"] = "No username is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!isset($params["password"])) {
        $payload["error"] = "No password is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if ($payload["machine"] = select_where(array("*"), "machines", array("host" => $params["host"], "username" => $params["username"], "password" => $params["password"]))) {
        $payload["error"] = "Machine already exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    } else
        unset($payload["machine"]);

    $query_params = array(
        "machine_name" => $params["machine_name"],
        "host" => $params["host"],
        "username" => $params["username"],
        "password" => $params["password"]
    );

    $machine_id = insert("machines", $query_params);
    $payload["machine"] = select_where(array("*"), "machines", array("id" => $machine_id));
    $payload["status"] = 1;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/machine/list', function (Request $request, Response $response, $args) {
    $payload = array("status" => 1);

    $machines = select(array("*"), "machines");
    $machine_result_array = array();
    foreach ($machines as $machine) {
        array_push($machine_result_array, get_object_vars($machine));
    }
    $payload["machines"] = $machine_result_array;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/machine/delete', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("status" => 0);
    if (!isset($params["id"])) {
        $payload["error"] = "No machine id is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!($payload["machine"] = select_where(array("*"), "machines", array("id" => $params["id"])))) {
        unset($payload["machine"]);
        $payload["machine"] = $params["id"];
        $payload["error"] = "Machine doesn't exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $query_params = array(
        "id" => $params["id"],
    );

    delete("machines", $query_params);
    $payload["status"] = 1;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

######################################################################################################################
/*
        CRON
    */
######################################################################################################################

$app->post('/cron/add', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("status" => 0);
    if (!isset($params["command"])) {
        $payload["error"] = "No command is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!isset($params["schedule"])) {
        $payload["error"] = "No schedule is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if ($payload["cron_job"] = select_where(array("*"), "cron_jobs", array("schedule" => $params["schedule"], "command" => $params["command"], "machine_id" => "*"))) {
        $payload["error"] = "Cron already exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    } else
        unset($payload["cron_job"]);

    $query_params = array(
        "schedule" => $params["schedule"],
        "command" => $params["command"],
        "machine_id" => "*",
    );

    $cron_id = insert("cron_jobs", $query_params);

    $output = shell_exec('crontab -l');
    file_put_contents('/var/www/html/crontab.txt', $output . $params["schedule"] . " php -f /var/www/html/cron.php id=$cron_id" . PHP_EOL);
    echo exec('crontab /var/www/html/crontab.txt');

    $payload["cron_job"] = select_where(array("*"), "cron_jobs", array("id" => $cron_id));
    $payload["status"] = 1;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/cron/add/{machine_id}', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("status" => 0);
    if (!isset($params["command"])) {
        $payload["error"] = "No command is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!isset($params["schedule"])) {
        $payload["error"] = "No schedule is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!select_where(array("*"), "machines", array("id" => $args["machine_id"]))) {
        $payload["schedule"] = $params["schedule"];
        $payload["command"] = $params["command"];
        $payload["error"] = "Machine not found.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if ($payload["cron_job"] = select_where(array("*"), "cron_jobs", array("schedule" => $params["schedule"], "command" => $params["command"], "machine_id" => $args["machine_id"]))) {
        $payload["error"] = "Cron already exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    } else
        unset($payload["cron_job"]);

    $query_params = array(
        "schedule" => $params["schedule"],
        "command" => $params["command"],
        "machine_id" => $args["machine_id"],
    );

    $cron_id = insert("cron_jobs", $query_params);

    $output = shell_exec('crontab -l');
    file_put_contents('/var/www/html/crontab.txt', $output . $params["schedule"] . " php -f /var/www/html/cron.php id=$cron_id" . PHP_EOL);
    echo exec('crontab /var/www/html/crontab.txt');

    $payload["cron_job"] = select_where(array("*"), "cron_jobs", array("id" => $cron_id));
    $payload["status"] = 1;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/cron/list', function (Request $request, Response $response, $args) {
    $payload = array("status" => 1);

    $cron_jobs = select(array("*"), "cron_jobs");
    $cron_result_array = array();
    foreach ($cron_jobs as $cron) {
        array_push($cron_result_array, get_object_vars($cron));
    }
    $payload["cron_jobs"] = $cron_result_array;
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/cron/delete', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("status" => 0);
    if (!isset($params["id"])) {
        $payload["error"] = "No cron job id is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!($payload["cron_job"] = select_where(array("*"), "cron_jobs", array("id" => $params["id"])))) {
        unset($payload["cron_job"]);
        $payload["id"] = $params["id"];
        $payload["error"] = "Cron job doesn't exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $query_params = array(
        "id" => $params["id"],
    );

    delete("cron_jobs", $query_params);

    $cron_job = get_object_vars($payload["cron_job"][0]);

    $output = shell_exec('crontab -l');
    file_put_contents('/var/www/html/crontab.txt', str_replace($cron_job["schedule"] . " php -f /var/www/html/cron.php id=" . $params["id"] . "\n", "", $output));
    echo exec('crontab /var/www/html/crontab.txt');

    $payload["status"] = 1;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

// Run app
$app->run();
