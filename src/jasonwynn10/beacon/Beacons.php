<?php
declare(strict_types=1);
namespace jasonwynn10\beacon;

use jasonwynn10\beacon\block\Beacon;
use jasonwynn10\beacon\inventory\BeaconInventory;
use jasonwynn10\beacon\packet\InventoryTransactionPacketV2;
use jasonwynn10\beacon\tile\Beacon as BeaconTile;
use pocketmine\Achievement;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\event\Listener;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;

class Beacons extends PluginBase implements Listener {
	protected static $inventories = [];
	public function onLoad() {
		PacketPool::registerPacket(new InventoryTransactionPacketV2());
		Achievement::add("create_full_beacon", "Beaconator", ["Create a full beacon"]);
		/** @noinspection PhpUnhandledExceptionInspection */
		Tile::registerTile(BeaconTile::class, [BeaconTile::BEACON, "minecraft:beacon"]);
		BlockFactory::registerBlock(new Beacon(), true);
		Item::addCreativeItem(new ItemBlock(Block::BEACON));
		$this->getServer()->getCraftingManager()->registerShapedRecipe(
			new ShapedRecipe(
				[
					"aaa",
					"aba",
					"ccc"
				],
				[
					"a" => ItemFactory::get(Item::GLASS),
					"b" => ItemFactory::get(Item::NETHER_STAR),
					"c" => ItemFactory::get(Item::OBSIDIAN)
				],
				[ItemFactory::get(Item::BEACON)]
			)
		);
	}

	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @param Player $player
	 *
	 * @return BeaconInventory|null
	 */
	public static function getBeaconInventory(Player $player) : ?BeaconInventory {
		return self::$inventories[$player->getName()] ?? null;
	}

	/**
	 * @param Player $player
	 * @param BeaconTile $beacon
	 */
	public static function setBeaconInventory(Player $player, \jasonwynn10\beacon\tile\Beacon $beacon) {
		self::$inventories[$player->getName()] = $beacon->getInventory();
	}
}