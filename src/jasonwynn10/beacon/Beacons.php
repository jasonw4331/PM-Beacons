<?php
declare(strict_types=1);
namespace jasonwynn10\beacon;

use jasonwynn10\beacon\block\Beacon;
use pocketmine\Achievement;
use pocketmine\block\BlockFactory;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\plugin\PluginBase;

class Beacons extends PluginBase implements Listener {
	public function onEnable() {
		BlockFactory::registerBlock(new Beacon(), true);
		Achievement::add("buildBeacon", "Beaconator", ["Create a full beacon"]);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacket(DataPacketReceiveEvent $event) {
		/** @var InventoryTransactionPacket $packet */
		$packet = $event->getPacket();
		if($packet::NETWORK_ID === InventoryTransactionPacket::NETWORK_ID) {
			$cancel = false;
			$player = $event->getPlayer();
			foreach($packet->actions as $networkInventoryAction) {
				if($networkInventoryAction === NetworkInventoryAction::SOURCE_TODO) {
					switch($networkInventoryAction->windowId) {
						case NetworkInventoryAction::SOURCE_TYPE_BEACON:
							$cancel = true;
							// TODO
						break;
						case NetworkInventoryAction::SOURCE_TYPE_CONTAINER_DROP_CONTENTS:
							// TODO
						break;
					}
				}
			}
			$event->setCancelled($cancel);
		}
		echo $packet->getName()."\n"; // TODO: remove
	}
}