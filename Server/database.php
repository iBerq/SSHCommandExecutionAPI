<?php
function db_connection()
{
    $db = new PDO("mysql:host=database;dbname=ssh_cmd_exec_app", "server", "server123@");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    return $db;
}
function insert($table, $params)
{
    $conn = db_connection();
    $sql = "INSERT INTO $table (";
    foreach ($params as $key => $value) {
        $sql .= $key . ",";
    }
    $sql = substr($sql, 0, -1);
    $sql .= ") VALUES (";
    foreach ($params as $key => $value) {
        $sql .= ":$key,";
    }
    $sql = substr($sql, 0, -1);
    $sql .= ")";
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
    $id = $conn->lastInsertId();
    $conn = null;
    return $id;
}
function update($table, $params, $id)
{
    $conn = db_connection();
    $sql = "UPDATE $table SET ";
    foreach ($params as $key => $value) {
        $sql .= $key . "=:$key,";
    }
    $sql = substr($sql, 0, -1);
    foreach ($id as $key => $value) {
        $sql .= " WHERE $key=:$key";
    }
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    foreach ($id as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
    $conn = null;
}
function delete($table, $params)
{
    $conn = db_connection();
    $sql = "DELETE FROM $table WHERE ";
    foreach ($params as $key => $value) {
        $sql .= $key . "=:$key AND ";
    }
    $sql = substr($sql, 0, -4);
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
    $conn = null;
}
function select_where($columns, $table, $params)
{
    $conn = db_connection();
    $sql = "SELECT ";
    foreach ($columns as $column) {
        $sql .= $column . ",";
    }
    $sql = substr($sql, 0, -1);
    $sql .= " FROM $table WHERE ";
    foreach ($params as $key => $value) {
        $sql .= $key . "=:$key AND ";
    }
    $sql = substr($sql, 0, -4);
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
    $result = $stmt->fetchAll();
    $conn = null;
    return $result;
}
function select($columns, $table)
{
    $conn = db_connection();
    $sql = "SELECT ";
    foreach ($columns as $column) {
        $sql .= $column . ",";
    }
    $sql = substr($sql, 0, -1);
    $sql .= " FROM $table";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    $conn = null;
    return $result;
}
