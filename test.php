<?php
namespace Stanford\CatchUserSync;
/** @var CatchUserSync $module */


$module->updateREDCapFromSql();

exit();


$module->initialize();


// Load current REDCap Data
$module->loadRcData();
$module->emDebug(count($module->rcData) . " REDCap records loaded");
// $module->emDebug($module->rcData);


$module->connect();
$conn = $module->getConn();
$sql = "select participantId, cell, firstName, lastName, email from participants";
// $sql = "select top 10 participantId, cell, firstName, lastName, email from participants";

$query = $conn->prepare($sql);
$query->execute();

$payload = [];
$rowCount = 0;
while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
    $rowCount++;
    $rowHash = md5(serialize($row));
    // $module->emDebug("Hash: " . $rowHash);

    $record_id = $row['participantId'];

    if (isset($module->rcData[$record_id])
        && $rowHash == $module->rcData[$record_id]) {
        // Same
        // $module->emDebug('skipping');
        continue;
    }

    $payload[] = [
        "record_id" => $record_id,
        "email" => $row['email'],
        "phone" => $row['cell'],
        "first_name" => $row['firstName'],
        "last_name" => $row['lastName'],
        "hash" => $rowHash
    ];
}

$module->emDebug(count($payload) . " updates to REDCap from $rowCount CATCH Users");

if (count($payload)) {
    // Update REDCap
    $module->emDebug("Save:", $module->rc->importRecords($payload));
}










print "Done";
