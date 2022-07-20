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
    $payload = array();
    if (!isset($params["command"])) {
        $payload["message"] = "No command is given.";
    } else {
        $payload["command"] = $params["command"];
        $machines = select(array("machine_name"), "machines");
        $machine_result_array = array();
        foreach ($machines as $temp_machine) {
            $machine = get_object_vars($temp_machine);
            $machine_result = array("result" => 0, "machine_name" => $machine["machine_name"]);
            $output = "";
            $error = "";
            if (!executeCmdOnSSH($machine["machine_name"], $params["command"], $output, $error))
                $machine_result["message"] = $error;
            else {
                if ($error == "")
                    $machine_result["result"] = 1;
                else
                    $machine_result["message"] = $error;
                $machine_result["output"] = $output;
            }

            $query_params = array(
                "machine_name" => $machine_result["machine_name"],
                "command" => $params["command"],
                "runned_by" => (isset($params["runned_by"]) && $params["runned_by"] == "cron") ? "cron" : "manual",
                "result" => $machine_result["result"],
                "output" => isset($machine_result["output"]) ? $machine_result["output"] : "",
                "message" => isset($machine_result["message"]) ? $machine_result["message"] : "",
            );

            $command_id = insert("command_history", $query_params);

            array_push($machine_result_array, select_where(array("*"), "command_history", array("command_id" => $command_id)));
        }
        $payload["machine_results"] = $machine_result_array;
    }

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/command/exec/{machine_name}', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("result" => 0, "machine_name" => $args["machine_name"]);
    if (!isset($params["command"])) {
        $payload["message"] = "No command is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!select_where(array("*"), "machines", array("machine_name" => $args["machine_name"]))) {
        $payload["command"] = $params["command"];
        $payload["message"] = "Machine not found.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
    unset($payload["machine_name"]);

    $output = "";
    $error = "";
    if (!executeCmdOnSSH($args["machine_name"], $params["command"], $output, $error))
        $command["message"] = $error;
    else {
        if ($error == "")
            $payload["result"] = 1;
        else
            $command["message"] = $error;
        $command["output"] = $output;
    }

    $query_params = array(
        "machine_name" => $args["machine_name"],
        "command" => $params["command"],
        "runned_by" => (isset($params["runned_by"]) && $params["runned_by"] == "cron") ? "cron" : "manual",
        "result" => $payload["result"],
        "output" => isset($command["output"]) ? $command["output"] : "",
        "message" => isset($command["message"]) ? $command["message"] : "",
    );

    $command_id = insert("command_history", $query_params);

    $payload["command"] = select_where(array("*"), "command_history", array("command_id" => $command_id));

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
    $payload = array("result" => 0, "machine_name" => $args["machine_name"]);
    if (!($payload["history"] = select_where(array("*"), "command_history", array("machine_name" => $args["machine_name"])))) {
        unset($payload["history"]);
        $payload["message"] = "Machine not found.";
    } else
        $payload["result"] = 1;
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
    $payload = array("result" => 0);
    if (!isset($params["machine_name"])) {
        $payload["message"] = "No machine name is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if ($payload["machine"] = select_where(array("*"), "machines", array("machine_name" => $params["machine_name"]))) {
        $payload["message"] = "Machine already exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    } else
        unset($payload["machine"]);

    $query_params = array(
        "machine_name" => $params["machine_name"],
    );

    insert("machines", $query_params);
    $payload["machine"] = select_where(array("*"), "machines", array("machine_name" => $params["machine_name"]));
    $payload["result"] = 1;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/machine/list', function (Request $request, Response $response, $args) {
    $payload = array("result" => 1);

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
    $payload = array("result" => 0);
    if (!isset($params["machine_name"])) {
        $payload["message"] = "No machine name is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!($payload["machine"] = select_where(array("*"), "machines", array("machine_name" => $params["machine_name"])))) {
        unset($payload["machine"]);
        $payload["machine"] = $params["machine_name"];
        $payload["message"] = "Machine doesn't exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $query_params = array(
        "machine_name" => $params["machine_name"],
    );

    delete("machines", $query_params);
    $payload["result"] = 1;

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
    $payload = array("result" => 0);
    if (!isset($params["command"])) {
        $payload["message"] = "No command is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!isset($params["schedule"])) {
        $payload["message"] = "No schedule is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if ($payload["cron_job"] = select_where(array("*"), "cron_jobs", array("schedule" => $params["schedule"], "command" => $params["command"], "machine_name" => "*"))) {
        $payload["message"] = "Cron already exists.";
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
    file_put_contents('/var/www/html/crontab.txt', $output . $params["schedule"] . " php -f /var/www/html/cron.php cron_id=$cron_id" . PHP_EOL);
    echo exec('crontab /var/www/html/crontab.txt');

    $payload["cron_job"] = select_where(array("*"), "cron_jobs", array("cron_id" => $cron_id));
    $payload["result"] = 1;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/cron/add/{machine_name}', function (Request $request, Response $response, $args) {
    $params = (array)$request->getParsedBody();
    $payload = array("result" => 0);
    if (!isset($params["command"])) {
        $payload["message"] = "No command is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!isset($params["schedule"])) {
        $payload["message"] = "No schedule is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!select_where(array("*"), "machines", array("machine_name" => $args["machine_name"]))) {
        $payload["schedule"] = $params["schedule"];
        $payload["command"] = $params["command"];
        $payload["message"] = "Machine not found.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if ($payload["cron_job"] = select_where(array("*"), "cron_jobs", array("schedule" => $params["schedule"], "command" => $params["command"], "machine_name" => $args["machine_name"]))) {
        $payload["message"] = "Cron already exists.";
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
    file_put_contents('/var/www/html/crontab.txt', $output . $params["schedule"] . " php -f /var/www/html/cron.php cron_id=$cron_id" . PHP_EOL);
    echo exec('crontab /var/www/html/crontab.txt');

    $payload["cron_job"] = select_where(array("*"), "cron_jobs", array("cron_id" => $cron_id));
    $payload["result"] = 1;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/cron/list', function (Request $request, Response $response, $args) {
    $payload = array("result" => 1);

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
    $payload = array("result" => 0);
    if (!isset($params["cron_id"])) {
        $payload["message"] = "No cron job id is given.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    if (!($payload["cron_job"] = select_where(array("*"), "cron_jobs", array("cron_id" => $params["cron_id"])))) {
        unset($payload["cron_job"]);
        $payload["cron_id"] = $params["cron_id"];
        $payload["message"] = "Cron job doesn't exists.";
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $query_params = array(
        "cron_id" => $params["cron_id"],
    );

    delete("cron_jobs", $query_params);

    $cron_job = get_object_vars($payload["cron_job"][0]);

    $output = shell_exec('crontab -l');
    file_put_contents('/var/www/html/crontab.txt', str_replace($cron_job["schedule"] . " php -f /var/www/html/cron.php cron_id=" . $params["cron_id"] . "\n", "", $output));
    echo exec('crontab /var/www/html/crontab.txt');

    $payload["result"] = 1;

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

// Run app
$app->run();
