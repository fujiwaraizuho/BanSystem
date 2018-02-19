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
			if (!array_key_exists(Ban::PLUGIN_NAME, $player->formId)) return;
			if ($formId === (int) $player->formId[Ban::PLUGIN_NAME][Ban::SELECT_IS_TOP]) {

				switch ($formData) {

					case Ban::IS_ONLINE:

						$player->addTitle("§l§aSelected Online button！");
						$player->formId[Ban::PLUGIN_NAME][Ban::SELECT_IS_TOP] = null;

						break;
						
					case Ban::IS_OFFLINE:

						$player->addTitle("§l§cSelected Offline button！");
						$player->formId[Ban::PLUGIN_NAME][Ban::SELECT_IS_TOP] = null;

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

						$player->formId[Ban::PLUGIN_NAME][Ban::SEARCH_PLAYER_TOP] = $formId;

						$player->dataPacket($pk);
						$player->formId[Ban::PLUGIN_NAME][Ban::SELECT_IS_TOP] = null;

						break;
				}

			} else if ($formId === (int) $player->formId[Ban::PLUGIN_NAME][Ban::SEARCH_PLAYER_TOP]) {
				if (!empty($formData[0])) {
				    if (strlen($formData[0]) >= 15) {
				        $player->sendMessage("§c>> 名前が15文字以上のプレイヤーなんて存在しないんだよ？");
                        $player->formId[Ban::PLUGIN_NAME][Ban::SEARCH_PLAYER_TOP] = null;
                        return;
                    }

					$list = $this->db->search_list_player($formData[0]);
					$formData = [
						"type" => "form",
						"title" => "§l検索結果",
						"content" => "",
						"buttons" => []
					];

					for ($i = 0; $i < $list["amount"]; $i++) {
						$name = $list[$i];
						$formData["buttons"][] = ["text" => $name];
                        $playerlist[] = $name;
					}

					$pk = new ModalFormRequestPacket();

					$pk->formId = ($formId = mt_rand(1, 999999999));
					$pk->formData = json_encode($formData);

					$player->dataPacket($pk);

					$player->list = $playerlist;
					$player->formId[Ban::PLUGIN_NAME][Ban::SEARCH_PLAYER_SELECT] = $formId;
                    $player->formId[Ban::PLUGIN_NAME][Ban::SEARCH_PLAYER_TOP] = null;
				} else {
					$player->sendMessage("§c>> 名前がないプレイヤーなんて存在しないんだよ？");
					$player->formId[Ban::PLUGIN_NAME][Ban::SEARCH_PLAYER_TOP] = null;
				}

			} else if ($formId === (int) $player->formId[Ban::PLUGIN_NAME][Ban::SEARCH_PLAYER_SELECT]) {
			    if (isset($formData)) {
                    if (isset($player->list)) {
                        $searchName = $player->list[$formData];
                        $playerData = $this->db->search_player($searchName);

                        $data = [
                            "type" => "form",
                            "title" => "§l". $searchName ."様の情報",
                            "content" => "§a[ XUID ]\n".
                                         "§f". $playerData["xuid"] ."\n\n".
                                         "§a[ IP ]\n".
                                         "§f". $playerData["ip"] ."\n\n".
                                         "§a[ CID ]\n".
                                         "§f". $playerData["cid"] ."\n\n".
                                         "§a[ HOST ]\n".
                                         "§f". $playerData["host"] ."\n\n".
                                         "§l§c情報の取り扱いには注意してください！",
                            "buttons" => []
                        ];

                        $pk = new ModalFormRequestPacket();

                        $pk->formId = mt_rand(1, 999999999);
                        $pk->formData = json_encode($data);

                        $player->dataPacket($pk);

                        $player->formId[Ban::PLUGIN_NAME][Ban::SEARCH_PLAYER_SELECT] = null;
                        unset($player->list);
                    }
                }
            }
		}	
	}
}