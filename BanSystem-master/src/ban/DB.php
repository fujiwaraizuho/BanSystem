<?php

namespace ban;

/* Base */
use pocketmine\Player;

class DB
{
    /**
     * DB constructor.
     * @param string $dir
     * @param Ban $owner
     */
    public function __construct(string $dir, Ban $owner)
	{
		$this->db = new \SQLite3($dir . "data.db");
		$this->db->exec("CREATE TABLE IF NOT EXISTS userdata (
				name TEXT,
				xuid TEXT,
				ip TEXT,
				cid TEXT,
				uuid TEXT,
				host TEXT
		)");

		$this->db->exec("CREATE TABLE IF NOT EXISTS bandata (
				name TEXT,
				xuid TEXT,
				reason TEXT
		)");

		$this->owner = $owner;
	}


    /**
     * @param Player $player
     */
    public function registerUser(Player $player)
	{
		$value = "INSERT INTO userdata (name,xuid, ip, cid, uuid, host) VALUES (:name, :xuid, :ip, :cid, :uuid, :host)";
		$db = $this->db->prepare($value);

		$name = strtolower($player->getName());
		$xuid = $player->getXuid();
		$ip   = $player->getAddress();
		$cid  = $player->getClientId();
		$uuid = $player->getUniqueId();
		$host = gethostbyaddr($ip);

		$db->bindValue(":name", $name, SQLITE3_TEXT);
		$db->bindValue(":xuid", $xuid, SQLITE3_TEXT);
		$db->bindValue(":ip", $ip, SQLITE3_TEXT);
		$db->bindValue(":cid", $cid, SQLITE3_TEXT);
		$db->bindValue(":uuid", $uuid, SQLITE3_TEXT);
		$db->bindValue(":host", $host, SQLITE3_TEXT);

		$db->execute();

		$this->owner->logger->notice($name. " Register Account！");
	}

    /** TEST CODE START */
    public function add_test_user(string $name)
	{
		$value = "INSERT INTO userdata (name,xuid, ip, cid, uuid, host) VALUES (:name, :xuid, :ip, :cid, :uuid, :host)";
		$db = $this->db->prepare($value);

		$db->bindValue(":name", $name, SQLITE3_TEXT);
		$db->bindValue(":xuid", "TEST_USER", SQLITE3_TEXT);
		$db->bindValue(":ip", "TEST_USER", SQLITE3_TEXT);
		$db->bindValue(":cid", "TEST_USER", SQLITE3_TEXT);
		$db->bindValue(":uuid", "TEST_USER", SQLITE3_TEXT);
		$db->bindValue(":host", "TEST_USER", SQLITE3_TEXT);

		$db->execute();

		$this->owner->logger->notice($name. " Register Account！");
	}


    public function remove_test_user(string $name)
	{
		$value = "DELETE FROM userdata WHERE name = :name";
		$db = $this->db->prepare($value);

		$db->bindValue(":name", $name, SQLITE3_TEXT);

		$db->execute();

		$this->owner->logger->notice($name. " Delete Account！");
	}
	/** TEST CODE END */


    /**
     * @param string $name
     * @return bool
     */
    public function exists_user(string $name)
	{
		$value = "SELECT name FROM userdata WHERE name = :name";
		$db = $this->db->prepare($value);

		$db->bindValue(":name", $name, SQLITE3_TEXT);

        $result = $db->execute()->fetchArray(SQLITE3_ASSOC);

		if (empty($result)) {
			return true;
		}

		return false;
	}


    /**
     * @param string $name
     * @return bool
     */
    public function search_player(string $name)
	{
		$value = "SELECT * FROM userdata WHERE name = :name";
		$db = $this->db->prepare($value);

		$db->bindValue(":name", $name, SQLITE3_TEXT);

		$result = $db->execute()->fetchArray(SQLITE3_ASSOC);

		if (empty($result)) {
			$this->owner->logger->warning("UserData Not Found！");
			return false;
		}

		foreach ($result as $key => $value) {
			$data[$key] = $value;
		}

		return $data;
	}


    /**
     * @param string $searchword
     * @return array|bool
     */
    public function search_list_player(string $searchword)
	{
		$value = "SELECT name FROM userdata WHERE name LIKE :word";
		$db = $this->db->prepare($value);
		$data = [];
		$i = 0;

		$db->bindValue(":word", $searchword. "%", SQLITE3_TEXT);
		$result = $db->execute();

		while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
			if (!isset($res["name"])) continue;

			$data[$i] = $res["name"];

			$i++;
		}

		$data["amount"] = $i;

		if (empty($result)) {
			$this->owner->logger->warning("User List Not Found！");
			return false;
		}

		return $data;
	}
}