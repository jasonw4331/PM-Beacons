<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\packet;

use jasonwynn10\beacon\network\types\inventory\NormalTransactionDataV2;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use UnexpectedValueException as PacketDecodeException;

class InventoryTransactionPacketV2 extends InventoryTransactionPacket {
	public $preDecodeOffset;
	protected function decodePayload() : void{
		$in = $this;
		$this->requestId = $in->readGenericTypeNetworkId();
		$this->requestChangedSlots = [];
		if($this->requestId !== 0){
			for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
				$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}

		$transactionType = $in->getUnsignedVarInt();

		switch($transactionType){
			case self::TYPE_NORMAL:
				$this->trData = new NormalTransactionDataV2();
			break;
			case self::TYPE_MISMATCH:
				$this->trData = new MismatchTransactionData();
			break;
			case self::TYPE_USE_ITEM:
				$this->trData = new UseItemTransactionData();
			break;
			case self::TYPE_USE_ITEM_ON_ENTITY:
				$this->trData = new UseItemOnEntityTransactionData();
			break;
			case self::TYPE_RELEASE_ITEM:
				$this->trData = new ReleaseItemTransactionData();
			break;
			default:
				throw new PacketDecodeException("Unknown transaction type $transactionType");
		}

		$in->preDecodeOffset = $this->offset;
		$this->trData->decode($in);
	}
}