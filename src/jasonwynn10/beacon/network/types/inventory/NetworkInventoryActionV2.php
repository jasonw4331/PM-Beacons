<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\network\types\inventory;

use jasonwynn10\beacon\Beacons;
use jasonwynn10\beacon\inventory\action\DeleteItemAction;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\transaction\action\CreativeInventoryAction;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\UIInventorySlotOffset;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\Player;

class NetworkInventoryActionV2 extends NetworkInventoryAction {
	/**
	 * @return InventoryAction|null
	 *
	 * @throws \UnexpectedValueException
	 */
	public function createInventoryAction(Player $player){
		$oldItem = $this->oldItem->getItemStack();
		$newItem = $this->newItem->getItemStack();
		if($oldItem->equalsExact($newItem)){
			//filter out useless noise in 1.13
			return null;
		}
		switch($this->sourceType){
			case self::SOURCE_CONTAINER:
				if($this->windowId === ContainerIds::UI and $this->inventorySlot > 0){
					if($this->inventorySlot === UIInventorySlotOffset::CREATED_ITEM_OUTPUT){
						return null; //useless noise
					}
					if($this->inventorySlot === UIInventorySlotOffset::BEACON_PAYMENT) {
						$window = Beacons::getBeaconInventory($player);
						$slot = $this->inventorySlot - 27;
					}elseif(array_key_exists($this->inventorySlot, UIInventorySlotOffset::CRAFTING2X2_INPUT)){
						$window = $player->getCraftingGrid();
						if($window->getGridWidth() !== CraftingGrid::SIZE_SMALL){
							throw new \UnexpectedValueException("Expected small crafting grid");
						}
						$slot = UIInventorySlotOffset::CRAFTING2X2_INPUT[$this->inventorySlot];
					}elseif(array_key_exists($this->inventorySlot, UIInventorySlotOffset::CRAFTING3X3_INPUT)){
						$window = $player->getCraftingGrid();
						if($window->getGridWidth() !== CraftingGrid::SIZE_BIG){
							throw new \UnexpectedValueException("Expected big crafting grid");
						}
						$slot = UIInventorySlotOffset::CRAFTING3X3_INPUT[$this->inventorySlot];
					}else{
						throw new \UnexpectedValueException("Unhandled magic UI slot offset $this->inventorySlot");
					}
				}else{
					$window = $player->getWindow($this->windowId);
					$slot = $this->inventorySlot;
				}
				if($window !== null){
					return new SlotChangeAction($window, $slot, $oldItem, $newItem);
				}

				throw new \UnexpectedValueException("Player " . $player->getName() . " has no open container with window ID $this->windowId");
			case self::SOURCE_WORLD:
				if($this->inventorySlot !== self::ACTION_MAGIC_SLOT_DROP_ITEM){
					throw new \UnexpectedValueException("Only expecting drop-item world actions from the client!");
				}

				return new DropItemAction($newItem);
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

				return new CreativeInventoryAction($oldItem, $newItem, $type);
			case self::SOURCE_TODO:
				//These types need special handling.
				switch($this->windowId){
					case self::SOURCE_TYPE_CRAFTING_RESULT:
					case self::SOURCE_TYPE_CRAFTING_USE_INGREDIENT:
						return null;
					case -10: // TODO: is beacon always -10 ?
						return new DeleteItemAction($oldItem, $newItem);
				}

				//TODO: more stuff
				throw new \UnexpectedValueException("Player " . $player->getName() . " has no open container with window ID $this->windowId");
			default:
				throw new \UnexpectedValueException("Unknown inventory source type $this->sourceType");
		}
	}
}