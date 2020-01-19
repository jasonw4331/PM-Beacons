<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\tile;


use jasonwynn10\beacon\inventory\BeaconInventory;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryEventProcessor;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\tile\Container;
use pocketmine\tile\ContainerTrait;
use pocketmine\tile\Spawnable;

class Beacon extends Spawnable implements InventoryHolder, Container {
	use ContainerTrait;

	public CONST BEACON = "beacon";

	public CONST TAG_LEVELS = "Levels";
	public CONST TAG_PRIMARY = "Primary";
	public CONST TAG_SECONDARY = "Secondary";

	const PYRAMID_BLOCKS = [BlockIds::DIAMOND_BLOCK, BlockIds::EMERALD_BLOCK, BlockIds::GOLD_BLOCK, BlockIds::IRON_BLOCK];

	/** @var BeaconInventory $inventory */
	protected $inventory;
	/** @var int $ticks */
	private $ticks = 0;
	/** @var int $tier */
	protected $tier = 0;
	/** @var int $primary */
	protected $primary = 0;
	/** @var int $secondary */
	protected $secondary = 0;

	/**
	 * Beacon constructor.
	 *
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt) {
		parent::__construct($level, $nbt);
	}

	/**
	 * @return bool
	 */
	public function onUpdate() : bool {
		if($this->closed) {
			return false;
		}

		$this->timings->startTiming();

		$ret = false;

		$currentTick = $this->getLevel()->getServer()->getTick();
		if($this->ticks + 80 === $currentTick) { // 80 ticks = 4 seconds
			$this->ticks = $currentTick;

			$levels = $this->checkPyramid();
			if($levels > 0) {
				if($this->tier > $levels) {
					$this->tier = 0; // Replicates delay when pyramid block broken. Effects resume when block replaced.
					$ret = true;
				}elseif($this->tier < $levels) {
					$this->tier = $levels;
					$ret = true;
				}else {
					$duration = 9 + ($levels * 2);
					$range = 10 + ($levels * 10);
					foreach($this->level->getPlayers() as $player) {
						if($player->distance($this) <= $range) {
							$effectId = $this->primary;
							if($effectId !== 0) {
								$player->addEffect(new EffectInstance(Effect::getEffect($effectId), $duration * 20));
							}
							$effectId = $this->secondary;
							if($effectId !== 0) {
								$player->addEffect(new EffectInstance(Effect::getEffect($effectId), $duration * 20));
							}
						}
					}
				}
			}
		}
		$this->timings->stopTiming();
		return $ret;
	}

	/**
	 * @return int
	 */
	public function checkPyramid() : int {
		$levels = 0;
		if($this->isSolidAbove())
			return $levels;
		for($y = 1; $y <= 4; $y++, $levels++) {
			for($x = 0; $x <= 1 + $levels; $x++) {
				for($z = 0; $z <= 1 + $levels; $z++) {
					$id = $this->level->getBlockIdAt($this->x + $x, $this->y - $y, $this->z + $z);
					if(!in_array($id, self::PYRAMID_BLOCKS)) {
						break 3;
					}
					$id = $this->level->getBlockIdAt($this->x - $x, $this->y - $y, $this->z - $z);
					if(!in_array($id, self::PYRAMID_BLOCKS)) {
						break 3;
					}
					$id = $this->level->getBlockIdAt($this->x + $x, $this->y - $y, $this->z - $z);
					if(!in_array($id, self::PYRAMID_BLOCKS)) {
						break 3;
					}
					$id = $this->level->getBlockIdAt($this->x - $x, $this->y - $y, $this->z + $z);
					if(!in_array($id, self::PYRAMID_BLOCKS)) {
						break 3;
					}
				}
			}
		}
		return $levels;
	}

	/**
	 * @return bool
	 */
	public function isSolidAbove() : bool {
		if($this->y === $this->getLevel()->getHighestBlockAt($this->x, $this->z))
			return false;
		for($i = $this->y; $i < $this->level->getWorldHeight(); $i++) {
			if(($block = $this->getLevel()->getBlockAt($this->x, $i, $this->z))->isSolid() && !$block->getId() === Block::BEACON)
				return true;
		}
		return false;
	}

	/**
	 * @param CompoundTag $nbt
	 */
	public function addAdditionalSpawnData(CompoundTag $nbt) : void {
		$nbt->setInt(self::TAG_LEVELS, $this->checkPyramid());
		$nbt->setInt(self::TAG_PRIMARY, 0);
		$nbt->setInt(self::TAG_SECONDARY, 0);
	}

	/**
	 * @param CompoundTag $nbt
	 * @param Vector3 $pos
	 * @param int|null $face
	 * @param null|Item $item
	 * @param null|Player $player
	 */
	public static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null) : void {
		$nbt->setInt(self::TAG_LEVELS, 0);
		$nbt->setInt(self::TAG_PRIMARY, 0);
		$nbt->setInt(self::TAG_SECONDARY, 0);
	}

	/**
	 * @inheritDoc
	 */
	protected function readSaveData(CompoundTag $nbt) : void {
		$this->tier = max(0, $nbt->getInt(self::TAG_LEVELS, 0, true));
		$this->primary = max(0, $nbt->getInt(self::TAG_PRIMARY, 0, true));
		$this->secondary = max(0, $nbt->getInt(self::TAG_SECONDARY, 0, true));

		$this->inventory = new BeaconInventory($this);
		$this->loadItems($nbt);

		$this->inventory->setEventProcessor(new class($this) implements InventoryEventProcessor{
			/** @var Beacon */
			private $beacon;

			public function __construct(Beacon $beacon) {
				$this->beacon = $beacon;
			}

			public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem, Item $newItem) : ?Item{
				$this->beacon->scheduleUpdate();
				return $newItem;
			}
		});
	}

	/**
	 * @inheritDoc
	 */
	protected function writeSaveData(CompoundTag $nbt) : void {
		$nbt->setInt(self::TAG_LEVELS, $this->tier);
		$nbt->setInt(self::TAG_PRIMARY, $this->primary);
		$nbt->setInt(self::TAG_SECONDARY, $this->secondary);
		$this->saveItems($nbt);
	}

	/**
	 * @return BeaconInventory
	 */
	public function getInventory() {
		return $this->inventory;
	}

	public function close() : void{
		if(!$this->closed){
			$this->inventory->removeAllViewers(true);
			$this->inventory = null;

			parent::close();
		}
	}

	/**
	 * @return int
	 */
	public function getTier() : int {
		return $this->tier;
	}

	/**
	 * @return int
	 */
	public function getPrimary() : int {
		return $this->primary;
	}

	/**
	 * @param int $primary
	 *
	 * @return self
	 */
	public function setPrimary(int $primary) : self {
		$this->primary = $primary;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getSecondary() : int {
		return $this->secondary;
	}

	/**
	 * @param int $secondary
	 *
	 * @return self
	 */
	public function setSecondary(int $secondary) : self {
		$this->secondary = $secondary;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function getRealInventory() {
		return $this->getInventory();
	}
}