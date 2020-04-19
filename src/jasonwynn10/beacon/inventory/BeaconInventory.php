<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\inventory;

use pocketmine\inventory\ContainerInventory;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;

class BeaconInventory extends ContainerInventory {
	/** @var Position */
	protected $holder;

	/**
	 * BeaconInventory constructor.
	 *
	 * @param Position $pos
	 */
	public function __construct(Position $pos) {
		parent::__construct($pos->asPosition());
	}

	/**
	 * @return int
	 */
	public function getNetworkType() : int {
		return WindowTypes::BEACON;
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
	public function getDefaultSize() : int {
		return 1;
	}

	/**
	 * @return Position
	 */
	public function getHolder() {
		return $this->holder;
	}

	/**
	 * @param Player $who
	 */
	public function onClose(Player $who) : void {
		parent::onClose($who);

		$this->dropContents($this->holder->getLevel(), $this->holder->add(0.5, 0.5, 0.5));
	}
}