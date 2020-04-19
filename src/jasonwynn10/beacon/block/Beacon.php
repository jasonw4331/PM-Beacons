<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\block;

use jasonwynn10\beacon\Beacons;
use jasonwynn10\beacon\tile\Beacon as BeaconTile;
use pocketmine\Achievement;
use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Beacon extends Block {
	protected $id = self::BEACON;

	public function __construct(int $meta = 0){
		parent::__construct(self::BEACON, $meta);
	}

	/**
	 * @return float
	 */
	public function getHardness() : float{
		return 3;
	}

	/**
	 * @return float
	 */
	public function getBlastResistance() : float {
		return 15;
	}

	/**
	 * @return int
	 */
	public function getLightLevel() : int {
		return 15;
	}

	/**
	 * @return string
	 */
	public function getName() : string {
		return "Beacon";
	}

	/**
	 * @return int
	 */
	public function getToolType() : int {
		return BlockToolType::TYPE_NONE;
	}

	/**
	 * @param Item $item
	 * @param Block $blockReplace
	 * @param Block $blockClicked
	 * @param int $face
	 * @param Vector3 $clickVector
	 * @param Player|null $player
	 * @return bool
	 */
	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool {
		/** @var BeaconTile $beacon */
		$beacon = BeaconTile::createTile(BeaconTile::BEACON, $this->getLevel(), BeaconTile::createNBT($this, $face, $item, $player));
		if($beacon->getLayers() > 3) {
			Achievement::broadcast($player, "create_full_beacon");
		}
		return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	/**
	 * @param Item $item
	 * @param Player|null $player
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null) : bool {
		if($player instanceof Player) {
			$t = $this->getLevel()->getTile($this);
			$beacon = null;
			if($t instanceof BeaconTile) {
				$beacon = $t;
			}else {
				/** @var BeaconTile $beacon */
				$beacon = BeaconTile::createTile(BeaconTile::BEACON, $this->getLevel(), BeaconTile::createNBT($this));
			}

			Beacons::setBeaconInventory($player, $beacon);
			$player->addWindow($beacon->getInventory());
		}
		return true;
	}

	/**
	 * @param Item $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onBreak(Item $item, Player $player = null) : bool {
		$t = $this->getLevel()->getTile($this);
		$beacon = null;
		if($t instanceof BeaconTile) {
			$beacon = $t;
		}else {
			/** @var BeaconTile $beacon */
			$beacon = BeaconTile::createTile(BeaconTile::BEACON, $this->getLevel(), BeaconTile::createNBT($this));
		}
		if(!$beacon->isMovable())
			return false;
		return parent::onBreak($item, $player);
	}
}