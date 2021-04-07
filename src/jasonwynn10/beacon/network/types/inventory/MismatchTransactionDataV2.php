<?php
declare(strict_types=1);

namespace jasonwynn10\beacon\network\types\inventory;

use pocketmine\network\mcpe\NetworkBinaryStream as PacketSerializer;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use UnexpectedValueException as PacketDecodeException;

class MismatchTransactionDataV2 extends TransactionDataV2 {

	public function getTypeId() : int{
		return InventoryTransactionPacket::TYPE_MISMATCH;
	}

	protected function decodeData(PacketSerializer $stream) : void{
		if(count($this->actions) > 0){
			throw new PacketDecodeException("Mismatch transaction type should not have any actions associated with it, but got " . count($this->actions));
		}
	}

	protected function encodeData(PacketSerializer $stream) : void{

	}

	public static function new() : self{
		return new self; //no arguments
	}
}