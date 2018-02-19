<?php

namespace ban;

/* Base */
use pocketmine\plugin\PluginBase;

/* Command */
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

/* Packet */
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;


class Ban extends PluginBase
{
	/* Plugin Name */
	const PLUGIN_NAME = "BansSystem";

	/* Form Select Number */
	const SELECT_IS_TOP = 1;
	const SEARCH_PLAYER_TOP = 2;
	const SEARCH_PLAYER_SELECT = 3;

	/* Form Output */
	const IS_ONLINE = 0;
	const IS_OFFLINE = 1;
	const PLAYER_SEARCH = 2;

	/* logger variable */
	public $logger;


    public function onEnable()
	{
		if (!file_exists($this->getDataFolder())) {
			mkdir($this->getDataFolder());
		}

		$this->db = new DB($this->getDataFolder(), $this);

		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this, $this->db), $this);

		$this->logger = $this->getLogger();

		$this->logger->info("§aINFO §f> §aEnabled...");
	}


    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args):bool
    {
		switch ($label) {
			case "/ban":

				$data = [
					"type" => "form",
					"title" => "§lBanSystem",
					"content" => "ボタンを押してください...",
					"buttons" => [
						[
							"text" => "§l§aオンライン §rプレイヤーをBanする"
						],
						[
							"text" => "§l§cオフライン §rプレイヤーをBanする"
						],
						[
							"text" => "§lプレイヤーを検索"
						]
					]
				];

				$pk = new ModalFormRequestPacket();

				$pk->formId = ($formId = mt_rand(1, 999999999)); // 原因不明だがPHP_INT_MIN ・ PHP_INT_MAXだと不安定
				$pk->formData = json_encode($data);

				$sender->formId[self::PLUGIN_NAME][self::SELECT_IS_TOP] = $formId;

				$sender->dataPacket($pk);

				return true;
				break;

			/** TEST COMMAND */
			case "/testuser":
			
				if (!isset($args[0])) return false;

				if ($args[0] === "add") {
				    if (!isset($args[1])) return false;
					$this->db->add_test_user($args[1]);
				} else if ($args[0] === "remove") {
					$this->db->remove_test_user();
				}

				return true;
				break;
		}
	}
}