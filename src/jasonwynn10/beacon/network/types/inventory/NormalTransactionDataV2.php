<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\network\types\inventory;

use pocketmine\network\mcpe\NetworkBinaryStream as PacketSerializer;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;

class NormalTransactionDataV2 extends TransactionDataV2{

	public function getTypeId() : int{
		return InventoryTransactionPacket::TYPE_NORMAL;
	}

	protected function decodeData(PacketSerializer $stream) : void{

	}

	protected function encodeData(PacketSerializer $stream) : void{

	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 *
	 * @return \pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData
	 */
	public static function new(array $actions) : self{
		$result = new self();
		$result->actions = $actions;
		return $result;
	}
}