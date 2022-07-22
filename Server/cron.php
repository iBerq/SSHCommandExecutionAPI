<?php
include 'database.php';
parse_str(implode('&', array_slice($argv, 1)), $_GET);
$cron_id = $_GET["id"];

$cron_job = get_object_vars(select_where(["*"], "cron_jobs", ["id" => $cron_id])[0]);

$values = array(
    "command" => $cron_job["command"],
    "runned_by" => "cron",
);

$post_url = "http://localhost/command/exec";
if ($cron_job["machine_name"] != "*")
    $post_url .= "/" . $cron_job["machine_name"];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $post_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($values));
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$resultJSON = @curl_exec($ch);
$result = json_decode($resultJSON, true);
update("cron_jobs", ["last_runned_command_id" => ($cron_job["machine_name"] == "*") ? $result["result"][0][0]["id"] : $result["result"][0]["id"]], ["id" => $cron_id]);
