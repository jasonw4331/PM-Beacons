<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\tile;


use jasonwynn10\beacon\inventory\BeaconInventory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\tile\Spawnable;

class Beacon extends Spawnable implements InventoryHolder {

	public CONST BEACON = "beacon";

	public CONST TAG_LEVELS = "Levels";
	public CONST TAG_PRIMARY = "Primary";
	public CONST TAG_SECONDARY = "Secondary";

	const PYRAMID_BLOCKS = [BlockIds::DIAMOND_BLOCK, BlockIds::EMERALD_BLOCK, BlockIds::GOLD_BLOCK, BlockIds::IRON_BLOCK];

	/** @var BeaconInventory $inventory */
	protected $inventory;
	/** @var int $ticks */
	private $ticks = 0;

	/**
	 * Beacon constructor.
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt) {
		parent::__construct($level, $nbt);
		$this->inventory = new BeaconInventory($this);
	}

	/**
	 * @return bool
	 */
	public function onUpdate() : bool {
		if($this->closed === true){
			return false;
		}
		$this->timings->startTiming();
		$ret = false;
		$currentTick = $this->getLevel()->getServer()->getTick();
		if($this->ticks + 80 === $currentTick) { // 80 ticks = 4 seconds
			$this->ticks = $currentTick;
			$levels = $this->checkPyramid();
			if($levels > 0) {
				if($this->namedtag->getInt(self::TAG_LEVELS, 0) === 0 and $this->namedtag->getInt(self::TAG_LEVELS) !== $levels) {
					$this->namedtag->setInt(self::TAG_LEVELS, 0); // Replicates delay when pyramid block broken. Effects resume when block replaced.
					$ret = true;
				}elseif($this->namedtag->getInt(self::TAG_LEVELS) !== $levels) {
					$this->namedtag->setInt(self::TAG_LEVELS, $levels);
					$ret = true;
				}else{
					$duration = 9 + ($levels * 2);
					switch($levels) {
						case 1:
							$range = 20;
							break;
						case 2:
							$range = 30;
							break;
						case 3:
							$range = 40;
							break;
						case 4:
							$range = 50;
							break;
					}
					if(isset($range))
						foreach($this->level->getPlayers() as $player) {
							if($player->distance($this) <= $range) {
								$effectId = $this->namedtag->getInt(self::TAG_PRIMARY, 0);
								if($effectId !== 0)
									$player->addEffect(new EffectInstance(Effect::getEffect($effectId), $duration * 20));
								$effectId = $this->namedtag->getInt(self::TAG_SECONDARY, 0);
								if($effectId !== 0)
									$player->addEffect(new EffectInstance(Effect::getEffect($effectId), $duration * 20));
							}
						}
				}
			}
		}
		$this->timings->stopTiming();
		return $ret;
	}

	/**
	 * @return BeaconInventory
	 */
	public function getInventory() {
		return $this->inventory;
	}

	/**
	 * @return int
	 */
	public function checkPyramid() : int {
		$levels = 0;
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
}