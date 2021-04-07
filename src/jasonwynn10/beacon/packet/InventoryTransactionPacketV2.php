<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\packet;

use jasonwynn10\beacon\network\types\inventory\MismatchTransactionDataV2;
use jasonwynn10\beacon\network\types\inventory\NormalTransactionDataV2;
use jasonwynn10\beacon\network\types\inventory\ReleaseItemTransactionDataV2;
use jasonwynn10\beacon\network\types\inventory\TransactionDataV2;
use jasonwynn10\beacon\network\types\inventory\UseItemOnEntityTransactionDataV2;
use jasonwynn10\beacon\network\types\inventory\UseItemTransactionDataV2;
use pocketmine\network\mcpe\NetworkSession as PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use UnexpectedValueException as PacketDecodeException;
use function count;

class InventoryTransactionPacketV2 extends InventoryTransactionPacket {
	public const NETWORK_ID = ProtocolInfo::INVENTORY_TRANSACTION_PACKET;

	public const TYPE_NORMAL = 0;
	public const TYPE_MISMATCH = 1;
	public const TYPE_USE_ITEM = 2;
	public const TYPE_USE_ITEM_ON_ENTITY = 3;
	public const TYPE_RELEASE_ITEM = 4;

	/** @var int */
	public $requestId;
	/** @var InventoryTransactionChangedSlotsHack[] */
	public $requestChangedSlots;
	/** @var TransactionDataV2 */
	public $trData;

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
				$this->trData = new MismatchTransactionDataV2();
			break;
			case self::TYPE_USE_ITEM:
				$this->trData = new UseItemTransactionDataV2();
			break;
			case self::TYPE_USE_ITEM_ON_ENTITY:
				$this->trData = new UseItemOnEntityTransactionDataV2();
			break;
			case self::TYPE_RELEASE_ITEM:
				$this->trData = new ReleaseItemTransactionDataV2();
			break;
			default:
				throw new PacketDecodeException("Unknown transaction type $transactionType");
		}

		$this->trData->decode($in);
	}

	protected function encodePayload() : void{
		$out = $this;
		$out->writeGenericTypeNetworkId($this->requestId);
		if($this->requestId !== 0){
			$out->putUnsignedVarInt(count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlots){
				$changedSlots->write($out);
			}
		}

		$out->putUnsignedVarInt($this->trData->getTypeId());

		$this->trData->encode($out);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleInventoryTransaction($this);
	}
}