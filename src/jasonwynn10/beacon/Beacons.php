<?php
declare(strict_types=1);
namespace jasonwynn10\beacon;

use jasonwynn10\beacon\block\Beacon;
use jasonwynn10\beacon\packet\InventoryTransactionPacketV2;
use jasonwynn10\beacon\tile\Beacon as BeaconTile;
use pocketmine\Achievement;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;

class Beacons extends PluginBase implements Listener {
	public function onLoad() {
		PacketPool::registerPacket(new InventoryTransactionPacketV2());
		Achievement::add("create_full_beacon", "Beaconator", ["Create a full beacon"]);
		/** @noinspection PhpUnhandledExceptionInspection */
		Tile::registerTile(BeaconTile::class, [BeaconTile::BEACON, "minecraft:beacon"]);
		BlockFactory::registerBlock(new Beacon(), true);
		Item::addCreativeItem(new ItemBlock(Block::BEACON));
	}

	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
}