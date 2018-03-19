<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\block;

use jasonwynn10\beacon\inventory\BeaconInventory;
use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Beacon extends Block {
	protected $id = self::BEACON;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
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
		// TODO: check pyramid
		// TODO: achievement
		return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	/**
	 * @param Item $item
	 * @param Player|null $player
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null) : bool {
		if($player instanceof Player) {
			$player->addWindow(new BeaconInventory($this));
		}
		return true;
	}

	/**
	 * @return int
	 */
	public function getVariantBitmask() : int{
		return 0;
	}
}