<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\inventory\action;

use jasonwynn10\beacon\Beacons;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\transaction\action\CreativeInventoryAction;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\Player;

class CustomInventoryAction extends NetworkInventoryAction {
	/**
	 * @param Player $player
	 *
	 * @return InventoryAction|null
	 *
	 * @throws \UnexpectedValueException
	 */
	public function createInventoryAction(Player $player){
		if($this->oldItem->equalsExact($this->newItem)){
			//filter out useless noise in 1.13
			return null;
		}
		switch($this->sourceType){
			case self::SOURCE_CONTAINER:
				if($this->windowId === ContainerIds::UI and $this->inventorySlot > 0){
					if($this->inventorySlot === 50){
						return null; //useless noise
					}
					if($this->inventorySlot === 27) { // slot 27 is Beacon UI
						$window = Beacons::getBeaconInventory($player);
						$slot = $this->inventorySlot - 27;
					}elseif($this->inventorySlot >= 28 and $this->inventorySlot <= 31){
						$window = $player->getCraftingGrid();
						if($window->getGridWidth() !== CraftingGrid::SIZE_SMALL){
							throw new \UnexpectedValueException("Expected small crafting grid");
						}
						$slot = $this->inventorySlot - 28;
					}elseif($this->inventorySlot >= 32 and $this->inventorySlot <= 40){
						$window = $player->getCraftingGrid();
						if($window->getGridWidth() !== CraftingGrid::SIZE_BIG){
							throw new \UnexpectedValueException("Expected big crafting grid");
						}
						$slot = $this->inventorySlot - 32;
					}else{
						throw new \UnexpectedValueException("Unhandled magic UI slot offset $this->inventorySlot");
					}
				}else{
					$window = $player->getWindow($this->windowId);
					$slot = $this->inventorySlot;
				}
				if($window !== null){
					return new SlotChangeAction($window, $slot, $this->oldItem, $this->newItem);
				}

				throw new \UnexpectedValueException("Player " . $player->getName() . " has no open container with window ID $this->windowId");
			case self::SOURCE_WORLD:
				if($this->inventorySlot !== self::ACTION_MAGIC_SLOT_DROP_ITEM){
					throw new \UnexpectedValueException("Only expecting drop-item world actions from the client!");
				}

				return new DropItemAction($this->newItem);
			case self::SOURCE_CREATIVE:
				switch($this->inventorySlot){
					case self::ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM:
						$type = CreativeInventoryAction::TYPE_DELETE_ITEM;
					break;
					case self::ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM:
						$type = CreativeInventoryAction::TYPE_CREATE_ITEM;
					break;
					default:
						throw new \UnexpectedValueException("Unexpected creative action type $this->inventorySlot");

				}

				return new CreativeInventoryAction($this->oldItem, $this->newItem, $type);
			case self::SOURCE_TODO:
				//These types need special handling.
				switch($this->windowId){
					case self::SOURCE_TYPE_CRAFTING_RESULT:
					case self::SOURCE_TYPE_CRAFTING_USE_INGREDIENT:
						return null;
					case -10: // TODO: is beacon always -10 ?
						return new DeleteItemAction($this->oldItem, $this->newItem);
				}

				//TODO: more stuff
				throw new \UnexpectedValueException("Player " . $player->getName() . " has no open container with window ID $this->windowId");
			default:
				throw new \UnexpectedValueException("Unknown inventory source type $this->sourceType");
		}
	}
}