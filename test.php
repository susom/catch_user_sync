<?php
namespace Stanford\CatchUserSync;
/** @var CatchUserSync $module */



$module->initialize();
$module->connect();
$conn = $module->getConn();


//
//
//
// use \PDO;
// use \PDOException;
//
$module->emDebug("On Test", $conn);
//
// echo "Hello";
//
// $serverName = "prodcatch-ondemand.sql.azuresynapse.net";
// $database   = "catch_v1";
// $username   = "ShcConnector";
// $password   = 'ubqU8gd30gmlZ75jT$p4';
//
//
// try {
//     $conn = new PDO(
//       "sqlsrv:server = tcp:$serverName,1433; Database = $database",
//       $username, $password);
//
//     $conn->setAttribute(
//         PDO::ATTR_ERRMODE,
//         PDO::ERRMODE_EXCEPTION );
// } catch ( PDOException $e ) {
//     $module->emDebug("Exception", $e);
//     print( "Error connecting to SQL Server." );
//     die(print_r($e));
// }

// $sql = "select participantId, cell, firstName, lastName, email from participants";
$sql = "select top 10 participantId, cell, firstName, lastName, email from participants";
$query = $conn->prepare($sql);
$query->execute();

$payload = [];
while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
    $record = [
        "record_id" => $row['participantId'],
        "email" => $row['email'],
        "phone" => $row['cell']
    ];

    $module->emDebug($row);
};






exit();


$sql = "select top 10 * from participants";

$connectionOptions = array(
    "Database" => "catch_v1", // update me
    "UID" => "ShcConnector", // update me
    "PWD" => 'ubqU8gd30gmlZ75jT$p4' // update me
);


// $connectionInfo = array("UID" => "{your_user_name}", "pwd" => "{your_password_here}",
//     "Database" => "{your_database}", "LoginTimeout" => 30, "Encrypt" => 1, "TrustServerCertificate" => 0);
// $serverName = "tcp:{your_server}.sql.azuresynapse.net,1433";
// $conn = sqlsrv_connect($serverName, $connectionInfo);



//Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);
$params = [];
$options = [];

$sql = "select participantId from participants";
$stmt = sqlsrv_query($conn, $sql, $params, $options);

if ($stmt === false) {
    $module->emDebug(sqlsrv_errors());
} else {

    $module->emDebug("Rows", sqlsrv_num_rows());

}

// echo("\n\nReading data from table" . PHP_EOL);
//
// $module->emDebug($getResults, sqlsrv_errors());
//
// if ($getResults == FALSE)
//     echo(sqlsrv_errors());
// while ($row = sqlsrv_fetch_array($getResults, SQLSRV_FETCH_ASSOC)) {
//     echo($row['participantId'] . " " . PHP_EOL);
// }
sqlsrv_free_stmt($stmt);


