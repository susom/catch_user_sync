<?php
namespace Stanford\CatchUserSync;

require_once "emLoggerTrait.php";

require('vendor/autoload.php');
use IU\PHPCap\RedCapProject;

class CatchUserSync extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

    private $apiUrl;
    private $apiToken;
    private $dbUrl;
    private $dbName;
    private $dbUser;
    private $dbPassword;

    private $conn;

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
    }

    public function connect() {
        try {

            $dsn = "sqlsrv:server = tcp:" . $this->dbUrl . ",1433; Database = " . $this->dbName;
            $this->emDebug($dsn);
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



	public function redcap_module_system_enable( $version ) {

	}


	public function redcap_module_project_enable( $version, $project_id ) {

	}


	public function redcap_module_save_configuration( $project_id ) {

	}


}
