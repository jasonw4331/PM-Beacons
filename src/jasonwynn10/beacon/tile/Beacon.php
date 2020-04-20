<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\tile;


use jasonwynn10\beacon\inventory\BeaconInventory;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\tile\Spawnable;

class Beacon extends Spawnable implements InventoryHolder {

	public CONST BEACON = "beacon";

	public CONST TAG_LEVELS = "levels";
	public CONST TAG_PRIMARY = "primary";
	public CONST TAG_SECONDARY = "secondary";
	public CONST TAG_MOVABLE = "isMovable";

	const PYRAMID_BLOCKS = [BlockIds::DIAMOND_BLOCK, BlockIds::EMERALD_BLOCK, BlockIds::GOLD_BLOCK, BlockIds::IRON_BLOCK];

	/** @var BeaconInventory $inventory */
	protected $inventory;
	/** @var int $ticks */
	private $ticks = 0;
	/** @var string[] $viewers */
	private $viewers = [];
	/** @var int $tier */
	protected $tier = 0;
	/** @var int $primary */
	protected $primary = 0;
	/** @var int $secondary */
	protected $secondary = 0;
	/** @var bool $movable */
	protected $movable = true;

	/**
	 * Beacon constructor.
	 *
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt) {
		parent::__construct($level, $nbt);
		$this->ticks = $this->getLevel()->getServer()->getTick();
		$this->scheduleUpdate();
	}

	/**
	 * @return bool
	 */
	public function onUpdate() : bool {
		if($this->closed) {
			return false;
		}

		$this->timings->startTiming();

		$this->checkViewers();

		$currentTick = $this->getLevel()->getServer()->getTick();
		if($this->ticks + 80 <= $currentTick) { // 80 ticks = 4 seconds
			$this->ticks = $currentTick;

			$levels = $this->getLayers();
			if($this->tier > $levels) {
				$this->tier = 0; // Replicates delay when pyramid block broken. Effects resume when block replaced.
				$this->spawnToAll();
			}else {
				if($this->tier < $levels) {
					$this->tier = $levels;
					$this->spawnToAll();
				}
				$duration = 9 + ($levels * 2);
				$range = 10 + ($levels * 10);
				foreach($this->level->getPlayers() as $player) {
					if($player->distance($this) <= $range) {
						$effectId = $this->primary;
						if($effectId !== 0) {
							$player->addEffect(new EffectInstance(Effect::getEffect($effectId), $duration * 20^2, 0, false));
						}
						$effectId = $this->secondary;
						if($effectId !== 0) {
							if($this->secondary == $this->primary) {
								$player->addEffect(new EffectInstance(Effect::getEffect($effectId), $duration * 20^2, 1, false));
							}else{
								$player->addEffect(new EffectInstance(Effect::getEffect($effectId), $duration * 20^2, 0, false));
							}
						}
					}
				}
			}
		}
		$this->timings->stopTiming();
		return true;
	}

	/**
	 * @return int
	 */
	public function getLayers() : int {
		$layers = 0;
		if($this->checkShape($this->getSide(0), 1))
			$layers++;
		else
			return $layers;
		if($this->checkShape($this->getSide(0, 2), 2))
			$layers++;
		else
			return $layers;
		if($this->checkShape($this->getSide(0, 3), 3))
			$layers++;
		else
			return $layers;
		if($this->checkShape($this->getSide(0, 4), 4))
			$layers++;

		return $layers;
	}

	/**
	 * @param Vector3 $pos
	 * @param int $layer
	 *
	 * @return bool
	 */
	public function checkShape(Vector3 $pos, $layer = 1) : bool {
		for($x = $pos->x - $layer; $x <= $pos->x + $layer; $x++)
			for($z = $pos->z - $layer; $z <= $pos->z + $layer; $z++)
				if(!in_array($this->getLevel()->getBlockIdAt($x, $pos->y, $z), [Block::DIAMOND_BLOCK, Block::IRON_BLOCK, Block::EMERALD_BLOCK, Block::GOLD_BLOCK]))
					return false;
		return true;
	}

	/**
	 * @return bool
	 */
	public function solidAbove() : bool {
		if($this->y === $this->getLevel()->getHighestBlockAt($this->x, $this->z))
			return false;
		for($i = $this->y; $i < $this->level->getWorldHeight(); $i++){
			if(($block = $this->getLevel()->getBlockAt($this->x, $i, $this->z))->isSolid() && $block->getId() !== Block::BEACON)
				return true;
		}
		return false;
	}

	public function spawnToAll(){
		if($this->closed){
			return;
		}
		// TODO: activate beam if no block above
		parent::spawnToAll();
	}

	/**
	 * @param CompoundTag $nbt
	 */
	public function addAdditionalSpawnData(CompoundTag $nbt) : void {
		$nbt->setInt(self::TAG_LEVELS, $this->getLayers());
		$nbt->setInt(self::TAG_PRIMARY, 0);
		$nbt->setInt(self::TAG_SECONDARY, 0);
		$nbt->setByte(self::TAG_MOVABLE, 1);
	}

	/**
	 * @param CompoundTag $nbt
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function updateCompoundTag(CompoundTag $nbt, Player $player) : bool {
		$this->tier = $this->getLayers();
		$this->primary = max(0, $nbt->getInt(self::TAG_PRIMARY, 0, true));
		$this->secondary = max(0, $nbt->getInt(self::TAG_SECONDARY, 0, true));
		$this->movable = (bool)max(0, $nbt->getByte(self::TAG_MOVABLE, 0, true));

		$this->scheduleUpdate();
		$this->spawnToAll();
		return true;
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
		$nbt->setByte(self::TAG_MOVABLE, 1);
	}

	/**
	 * @inheritDoc
	 */
	protected function readSaveData(CompoundTag $nbt) : void {
		$this->tier = max(0, $nbt->getInt(self::TAG_LEVELS, 0, true));
		$this->primary = max(0, $nbt->getInt(self::TAG_PRIMARY, 0, true));
		$this->secondary = max(0, $nbt->getInt(self::TAG_SECONDARY, 0, true));
		$this->movable = (bool)max(1, $nbt->getByte(self::TAG_MOVABLE, 1, true));

		$this->inventory = new BeaconInventory($this);
	}

	/**
	 * @inheritDoc
	 */
	protected function writeSaveData(CompoundTag $nbt) : void {
		$nbt->setInt(self::TAG_LEVELS, $this->tier);
		$nbt->setInt(self::TAG_PRIMARY, $this->primary);
		$nbt->setInt(self::TAG_SECONDARY, $this->secondary);
		$nbt->setByte(self::TAG_MOVABLE, (int)$this->movable);
	}

	/**
	 * @return BeaconInventory
	 */
	public function getInventory() : BeaconInventory {
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
	 * @return bool
	 */
	public function isMovable() : bool {
		return (bool)$this->movable;
	}

	/**
	 * @param bool $movable
	 *
	 * @return self
	 */
	public function setMovable(bool $movable) : self {
		$this->movable = $movable;
		return $this;
	}

	private function checkViewers() : void {
		$viewers = $this->level->getChunkPlayers($this->getFloorX() >> 4, $this->getFloorZ() >> 4);
		$names = [];
		$newViewers = [];
		foreach($viewers as $player) {
			if(!in_array($player->getName(), $this->viewers))
				$newViewers[] = $player->getName();
			$names[] = $player->getName();
		}
		foreach($newViewers as $name) {
			$player = $this->level->getServer()->getPlayerExact($name);
			$glass = BlockFactory::get(BlockIds::GLASS);
			$glass->position($this->asPosition());
			$beacon = BlockFactory::get(BlockIds::BEACON);
			$beacon->position($this->asPosition());
			if($player instanceof Player) {
				$this->getLevel()->getServer()->getPluginManager()->getPlugin("PM-Beacons")->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($player, $glass, $beacon) : void {
					if(!$this->level instanceof Level)
						return; // prevent crash on despawned tiles
					$this->getLevel()->sendBlocks([$player], [$glass], UpdateBlockPacket::FLAG_ALL_PRIORITY);
					$this->getLevel()->sendBlocks([$player], [$beacon], UpdateBlockPacket::FLAG_ALL_PRIORITY);
				}), 20);
			}
		}
		$this->viewers = $names;
	}
}