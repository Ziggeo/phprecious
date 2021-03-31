<?php


require_once(dirname(__FILE__) . "/../Database.php");
require_once(dirname(__FILE__) . "/RedshiftDatabaseTable.php");

class RedshiftDatabase extends Database {

	protected static function perfmon($enter) {
		global $PERFMON;
		if (@$PERFMON) {
			if ($enter)
				$PERFMON->enter("database");
			else
				$PERFMON->leave("database");
		}
	}

	private $connection;
	private $database;
	private $user;
	private $password;
	private $host;
	private $port;
	private $dbname;

	/**
	 * PostgreDatabase constructor.
	 * @param $user
	 * @param $password
	 * @param $host
	 * @param $port
	 * @param $dbname
	 */
	public function __construct()
	{
		if (func_num_args() <> 2) {
			$this->user = func_get_arg(0);
			$this->password = func_get_arg(1);
			$this->host = func_get_arg(2);
			$this->port = func_get_arg(3);
			$this->dbname = func_get_arg(4);
		} else {
			$this->dbname = func_get_arg(0);
			$parsed = parse_url(func_get_arg(1));
			$this->user = $parsed["user"];
			$this->password = $parsed["pass"];
			$this->host = $parsed["host"];
			$this->port = $parsed["port"];
		}
	}

	private function getConnection() {
		if (!$this->connection) {
			static::perfmon(true);
			try {
				$this->connection = new PDO("pgsql:host=$this->host;port=$this->port;dbname=$this->dbname",$this->user, $this->password);
			} catch (PDOException $e) {
				die($e->getMessage());
			}
			static::perfmon(false);
		}
		return $this->connection;
	}

	public function getDatabase() {
		if (!$this->database) {
			static::perfmon(true);
			$this->database = $this->getConnection();
			static::perfmon(false);
		}
		return $this->database;
	}

	public function selectTable($name, $definition = NULL) {
		return new RedshiftDatabaseTable($this, $name);
	}

	public function encode($type, $value) {
		return $value;
	}

	public function decode($type, $value) {
		return $value;
	}

	public function runQueryRaw($query_string) {
		$conn = $this->getDatabase();
		$query = $conn->prepare($query_string);
		$query->execute();
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}

	public function runQuery($query_string_base, $query_params) {
		$conn = $this->getDatabase();
		if (!empty($query_params["where"])) {
			$where_string = "";
			$where_params_ext = RedshiftDatabase::extractWhereParams($query_params["where"]);
			$i = 1;
			$params = array();
			foreach ($where_params_ext as $where_param) {
				extract($where_param);
				switch ($operator) {
					case "BETWEEN":
						$where_string .= " $base $operator :" . $where_id[0] . " AND :" .$where_id[1];
						$params[$where_id[0]] = $value[0];
						$params[$where_id[1]] = $value[1];
						break;
					case "IN":
						$where_string .= " $base $operator (";
						end($where_id);
						$last_key = key($where_id);
						foreach ($where_id as $id_w => $w) {
							$where_string .= ":$w";
							$params[$w] = $value[$id_w];
							if ($id_w !== $last_key) {
								$where_string .= ",";
							}
						}
						$where_string .= ")";
						break;
					default:
						$where_string .= " $base $operator :$where_id";
						$params[$where_id] = $value;
						break;

				}
				if ($i < count($where_params_ext))
					$where_string .= " AND";
				$i++;
			}
		}
		$query_string = str_replace(
			array("%where_params%"),
			$where_string,
			$query_string_base
		);

		$query = $conn->prepare($query_string);

		if (!empty($params)) {
			foreach ($params as $id_pa => &$param) {
				$query->bindParam(":$id_pa", $param);
			}
		}
		$query->execute();
		$err = $conn->errorInfo();
		if (!($err[0] <> 0)) {
			$ret = $query->fetchAll(PDO::FETCH_ASSOC);
		} else {
			$ret = "ERROR: " . $err[0] . " - " . $err[1] . ": " . $err[2];
		}

		return $ret;
	}

	public static function extractWhereParams($where_params) {
		$where_params_ext = array();

		foreach ($where_params as $where_param => $param) {
			$operator = "=";
			$where_param_name = $where_param;
			$value = $param;
			$where_id = rand(0, 1000000000);
			if (is_array($param) && isset($param["operator"])) {
				$operator = $param["operator"];
				$value = $param["value"];
				if (is_array($param["value"])) {
					$where_id = array();
					while (count($param["value"]) !== count($where_id)) {
						$where_id[] = rand(0, 1000000000);
					}
				}
			}
			$where_params_ext[] = array(
				"operator" => $operator,
				"value" => $value,
				"where_id" => $where_id,
				"name" => $where_param_name,
				"base" => $where_param_name
			);

		}

		return $where_params_ext;
	}
}

