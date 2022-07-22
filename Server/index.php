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
    $response->getBody()->write('Hello, World!');
    return $response;
})->setName('root');

$app->get('/test', function (Request $request, Response $response) {
    $response->getBody()->write('Hello, World!');
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

$app->post('/command/exec/{machine_name}', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("status" => 0, "machine_name" => $args["machine_name"]);
    if (!isset($params["command"])) {
        $payload["error"] = "No command is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!select_where(array("*"), "machines", array("machine_name" => $args["machine_name"]))) {
        $payload["command"] = $params["command"];
        $payload["error"] = "Machine not found.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
    unset($payload["machine_name"]);

    $job_id = insert("jobs", array("status" => 0));
    $params["job_id"] = $job_id;

    if (publish_job($params, $args["machine_name"])) {
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
        $machine_result_array[$machine["machine_name"]] = select_where(array("*"), "command_history", array("machine_name" => $machine["machine_name"]));
    }
    $payload["history"] = $machine_result_array;
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/command/history/{machine_name}', function (Request $request, Response $response, $args) {
    $payload = array("status" => 0, "machine_name" => $args["machine_name"]);

    if (!select_where(array("*"), "machines", array("machine_name" => $args["machine_name"])))
        $payload["error"] = "Machine not found.";
    else {
        $payload["status"] = 1;
        $payload["history"] = select_where(array("*"), "command_history", array("machine_name" => $args["machine_name"]));
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

    if ($payload["machine"] = select_where(array("*"), "machines", array("machine_name" => $params["machine_name"]))) {
        $payload["error"] = "Machine already exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    } else
        unset($payload["machine"]);

    $query_params = array(
        "machine_name" => $params["machine_name"],
    );

    insert("machines", $query_params);
    $payload["machine"] = select_where(array("*"), "machines", array("machine_name" => $params["machine_name"]));
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
    if (!isset($params["machine_name"])) {
        $payload["error"] = "No machine name is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!($payload["machine"] = select_where(array("*"), "machines", array("machine_name" => $params["machine_name"])))) {
        unset($payload["machine"]);
        $payload["machine"] = $params["machine_name"];
        $payload["error"] = "Machine doesn't exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $query_params = array(
        "machine_name" => $params["machine_name"],
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

    if ($payload["cron_job"] = select_where(array("*"), "cron_jobs", array("schedule" => $params["schedule"], "command" => $params["command"], "machine_name" => "*"))) {
        $payload["error"] = "Cron already exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    } else
        unset($payload["cron_job"]);

    $query_params = array(
        "schedule" => $params["schedule"],
        "command" => $params["command"],
        "machine_name" => "*",
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

$app->post('/cron/add/{machine_name}', function (Request $request, Response $response, $args) {
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

    if (!select_where(array("*"), "machines", array("machine_name" => $args["machine_name"]))) {
        $payload["schedule"] = $params["schedule"];
        $payload["command"] = $params["command"];
        $payload["error"] = "Machine not found.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if ($payload["cron_job"] = select_where(array("*"), "cron_jobs", array("schedule" => $params["schedule"], "command" => $params["command"], "machine_name" => $args["machine_name"]))) {
        $payload["error"] = "Cron already exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    } else
        unset($payload["cron_job"]);

    $query_params = array(
        "schedule" => $params["schedule"],
        "command" => $params["command"],
        "machine_name" => $args["machine_name"],
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
