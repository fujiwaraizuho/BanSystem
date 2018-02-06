<?php

namespace ban;

/* Base */
use pocketmine\plugin\PluginBase;

/* Event */
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\server\DataPacketReceiveEvent;

/* Packet */
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;

class EventListener implements Listener
{
    /**
     * EventListener constructor.
     * @param PluginBase $owner
     * @param $db
     */
    public function __construct(PluginBase $owner, $db)
	{
		$this->owner = $owner;
		$this->db = $db;
	}


    /**
     * @param PlayerLoginEvent $event
     */
    public function onLogin(PlayerLoginEvent $event)
	{
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		$exists = $this->db->exists_user($name);

		if ($exists) {
			$this->db->registerUser($player);
		}
	}


    /**
     * @param DataPacketReceiveEvent $event
     */
    public function onData(DataPacketReceiveEvent $event)
	{
		$packet = $event->getPacket();

		if ($packet instanceof ModalFormResponsePacket) {
			$formId = (int) $packet->formId;
			$formData = json_decode($packet->formData, true);
			$player = $event->getPlayer();

			// ONLINE or OFFLINE SELECT FORM
			if (is_null($formData)) return;
			if ($formId === (int) $player->formId[Ban::SELECT_IS_TOP]) {

				switch ($formData) {

					case Ban::IS_ONLINE:

						$player->addTitle("§l§aSelected Online button！");
						$player->formId[Ban::SELECT_IS_TOP] = null;

						break;
						
					case Ban::IS_OFFLINE:

						$player->addTitle("§l§cSelected Offline button！");
						$player->formId[Ban::SELECT_IS_TOP] = null;

						break;

					case Ban::PLAYER_SEARCH:
						
						$data = [
							"type" => "custom_form",
							"title" => "ユーザー情報検索",
							"content" => [
								[
									"type" => "input",
									"text" => "",
									"placeholder" => "ユーザー名"
								]
							]
						];

						$pk = new ModalFormRequestPacket();

						$pk->formId = ($formId = mt_rand(1, 999999999));
						$pk->formData = json_encode($data);

						$player->formId[Ban::SEARCH_PLAYER_TOP] = $formId;

						$player->dataPacket($pk);
						$player->formId[Ban::SELECT_IS_TOP] = null;

						break;
				}

			} else if ($formId === (int) $player->formId[Ban::SEARCH_PLAYER_TOP]) {
				if (!empty($formData[0])) {

					$data = $this->db->search_player($formData[0]);
					$list = $this->db->search_list_player($formData[0]);

					$formData = [
						"type" => "form",
						"title" => "§l検索結果",
						"content" => "",
						"buttons" => []
					];

					for ($i = 0; $i < $list["id"]; $i++) { 
						$name = $list[$i]["name"];
						$formData["buttons"][] = ["text" => $name]; 
					}

					$pk = new ModalFormRequestPacket();

					$pk->formId = 1111;
					$pk->formData = json_encode($formData);

					$player->dataPacket($pk);

					if (!empty($data)) {
						$player->sendMessage("§aNAME§f => ". $data["name"]);
						$player->sendMessage("§aXUID§f => ". $data["xuid"]);
						$player->sendMessage("§aIP§f   => ". $data["ip"]);
						$player->sendMessage("§aCID§f  => ". $data["cid"]);
						$player->sendMessage("§aUUID§f => ". $data["uuid"]);
						$player->sendMessage("§aHOST§f => ". $data["host"]);
					} else {
						$player->sendMessage("§c>> データーが登録されていません！");
					}

					$player->formId[Ban::SEARCH_PLAYER_TOP] = null;
				} else {
					$player->sendMessage("§c>> 名前がないプレイヤーなんて存在しないんだよ？");
					$player->formId[Ban::SEARCH_PLAYER_TOP] = null;
				}
			}
		}	
	}
}