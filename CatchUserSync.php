<?php
namespace Stanford\CatchUserSync;

require_once "emLoggerTrait.php";

require('vendor/autoload.php');

class CatchUserSync extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

    private $apiUrl;
    private $apiToken;
    private $dbUrl;
    private $dbName;
    private $dbUser;
    private $dbPassword;

    private $conn;

    public $rc;

    public $rcData;

    /**
     * @return mixed
     */
    public function getConn()
    {
        return $this->conn;
    }

    /**
     * @param mixed $conn
     */
    public function setConn($conn)
    {
        $this->conn = $conn;
    }

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}

	public function initialize() {
        $this->apiUrl = $this->getProjectSetting('api-url');
        $this->apiToken = $this->getProjectSetting('api-token');
        $this->dbUrl = $this->getProjectSetting('db-url');
        $this->dbName = $this->getProjectSetting('db-name');
        $this->dbUser = $this->getProjectSetting('db-user');
        $this->dbPassword = $this->getProjectSetting('db-password');

        try {
            $this->rc = new \IU\PHPCap\RedCapProject($this->apiUrl, $this->apiToken);
        } catch (\Exception $exception) {
            $this->emError("The following error occurred: {$exception->getMessage()}");
            // print $exception->getTraceAsString()."\n";
        }
    }


    /**
     * Make a connection to the PDO SQL Server as $this->conn
     * @return bool
     */
    public function connect() {
        try {

            $dsn = "sqlsrv:server = tcp:" . $this->dbUrl . ",1433; Database = " . $this->dbName;
            // $this->emDebug($dsn);
            $this->conn = new \PDO( $dsn, $this->dbUser, $this->dbPassword);

            $this->conn->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION );
            return true;
        } catch ( \PDOException $e ) {
            $this->emError("Exception", $e, "Error connecting to SQL Server." );
            return false;
        }
    }


    /**
     * Load the REDCap records and hashes into $this->rcData
     */
    public function loadRcData() {
        $results = $this->rc->exportRecords('php','flat',null,['record_id','hash']);
        foreach ($results as $record) {
            $this->rcData[$record['record_id']] = $record['hash'];
        }

    }


    /**
     *
     * @param $cron
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cronSync($cron) {
        // $this->framework->runPageInPageInProjectContextOnEnabledProjects("cronSync.php");
        foreach($this->framework->getProjectsWithModuleEnabled() as $localProjectId) {
            $_GET['pid'] = $localProjectId;
            $url = $this->getUrl("cronSync.php",true, false);
            $this->emDebug("Setting pid to $localProjectId", $url);

            $client = new \GuzzleHttp\Client;
            $response = $client->request('GET', $url, [
                \GuzzleHttp\RequestOptions::SYNCHRONOUS => true
            ]);
            $this->emDebug("Response", $response->getStatusCode(), $response->getContents());
        }
    }


    /**
     * This function does the magic
     */
    public function updateREDCapFromSql() {
        $tsStart = microtime(true);
        $msgs = [];

        $this->initialize();
        $this->loadRcData();
        $msg = "Cached " . count($this->rcData) . " REDCap records";
        $this->emDebug($msg);
        $msgs[] = $msg;

        // Make SQL Query
        $this->connect();
        $conn = $this->getConn();
        $sql = "select participantId, cell, firstName, lastName, email from participants";
        // $sql = "select top 10 participantId, cell, firstName, lastName, email from participants";
        $query = $conn->prepare($sql);
        $query->execute();

        // Process Results
        $payload = [];
        $rowCount = 0;
        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $rowCount++;
            $rowHash = md5(serialize($row));
            // $module->emDebug("Hash: " . $rowHash);

            $record_id = $row['participantId'];

            if (isset($this->rcData[$record_id])
                && $rowHash == $this->rcData[$record_id]) {
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

        $msg = count($payload) . " updates to REDCap from $rowCount CATCH Users";
        $msgs[] = $msg;
        $this->emDebug($msg);

        if (count($payload)) {
            // Update REDCap
            $this->rc->importRecords($payload);
        }

        $runtime = round ((microtime(true) - $tsStart) * 1000,1);
        $msgs[] = "Completed in $runtime";

        print "<pre>" . implode("\n",$msgs) . "</pre>";
    }







}
