<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\packet;

use jasonwynn10\beacon\inventory\action\CustomInventoryAction;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;

class InventoryTransactionPacketV2 extends InventoryTransactionPacket {
	protected function decodePayload(){
		$this->requestId = $this->readGenericTypeNetworkId();
		$this->requestChangedSlots = [];
		if($this->requestId !== 0){
			for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; ++$i){
				$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($this);
			}
		}

		$this->transactionType = $this->getUnsignedVarInt();

		$this->hasItemStackIds = $this->getBool();

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->actions[] = $action = (new CustomInventoryAction())->read($this, $this->hasItemStackIds);
		}

		$this->trData = new \stdClass();

		switch($this->transactionType){
			case self::TYPE_NORMAL:
			case self::TYPE_MISMATCH:
				//Regular ComplexInventoryTransaction doesn't read any extra data
			break;
			case self::TYPE_USE_ITEM:
				$this->trData->actionType = $this->getUnsignedVarInt();
				$this->getBlockPosition($this->trData->x, $this->trData->y, $this->trData->z);
				$this->trData->face = $this->getVarInt();
				$this->trData->hotbarSlot = $this->getVarInt();
				$this->trData->itemInHand = $this->getSlot();
				$this->trData->playerPos = $this->getVector3();
				$this->trData->clickPos = $this->getVector3();
				$this->trData->blockRuntimeId = $this->getUnsignedVarInt();
			break;
			case self::TYPE_USE_ITEM_ON_ENTITY:
				$this->trData->entityRuntimeId = $this->getEntityRuntimeId();
				$this->trData->actionType = $this->getUnsignedVarInt();
				$this->trData->hotbarSlot = $this->getVarInt();
				$this->trData->itemInHand = $this->getSlot();
				$this->trData->playerPos = $this->getVector3();
				$this->trData->clickPos = $this->getVector3();
			break;
			case self::TYPE_RELEASE_ITEM:
				$this->trData->actionType = $this->getUnsignedVarInt();
				$this->trData->hotbarSlot = $this->getVarInt();
				$this->trData->itemInHand = $this->getSlot();
				$this->trData->headPos = $this->getVector3();
			break;
			default:
				throw new \UnexpectedValueException("Unknown transaction type $this->transactionType");
		}
	}
}