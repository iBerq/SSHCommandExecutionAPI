<?php
    /* ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL); */
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Selective\BasePath\BasePathMiddleware;
    use Slim\Factory\AppFactory;
    include "exec.php";

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
    
    $app->post('/command/exec', function (Request $request, Response $response, $args) {
        $params = (array)$request->getParsedBody();
        $payload = array();
        if (!isset($params["command"])){
            $payload["message"] = "No command is given.";
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }
        $payload["command"] = $params["command"];
        include "machines.php";
        $machine_result_array = array();
        foreach($machines as $machine){
            $machine_result = array("result" => "fail", "machine" => $machine);
            $output = "";
            $error = "";
            if (!executeCmdOnSSH($machine, $params["command"], $output, $error))
                $machine_result["message"] = $error;
            else {
                if ($error == "")
                    $machine_result["result"] = "success";
                else
                    $machine_result["message"] = $error;
                $machine_result["output"] = $output;
            }
            array_push($machine_result_array,$machine_result);
        }
        $payload["machine_results"] = $machine_result_array;

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $app->post('/command/exec/{machine}', function (Request $request, Response $response, $args) {
        $params = (array)$request->getParsedBody();
        $payload = array("result" => "fail", "machine" => $args["machine"]);
        if (!isset($params["command"])){
            $payload["message"] = "No command is given.";
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }
        $payload["command"] = $params["command"];
        include "machines.php";
        if (!in_array($args["machine"], $machines)){
            $payload["message"] = "Machine not found.";
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $output = "";
        $error = "";
        if (!executeCmdOnSSH($args["machine"], $params["command"], $output, $error))
            $payload["message"] = $error;
        else {
            if ($error == "")
                $payload["result"] = "success";
            else
                $payload["message"] = $error;
            $payload["output"] = $output;
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Run app
    $app->run();
?> 

