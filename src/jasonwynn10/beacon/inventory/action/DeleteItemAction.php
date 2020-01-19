<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\inventory\action;

use pocketmine\inventory\transaction\action\CreativeInventoryAction;
use pocketmine\item\Item;
use pocketmine\Player;

class DeleteItemAction extends CreativeInventoryAction {

	public function __construct(Item $sourceItem, Item $targetItem){
		parent::__construct($sourceItem, $targetItem, CreativeInventoryAction::TYPE_DELETE_ITEM);
	}

	public function isValid(Player $source) : bool {
		return true;
	}
}