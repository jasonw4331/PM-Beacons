<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\inventory;

use jasonwynn10\beacon\tile\Beacon;
use pocketmine\inventory\ContainerInventory;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;

class BeaconInventory extends ContainerInventory {

	/** @var Beacon $holder */
	protected $holder;

	/**
	 * BeaconInventory constructor.
	 *
	 * @param Beacon $pos
	 */
	public function __construct(Beacon $pos) {
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
		return 3; //TODO
	}

	/**
	 * @return Beacon
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