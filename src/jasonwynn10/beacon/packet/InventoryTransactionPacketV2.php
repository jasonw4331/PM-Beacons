<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\packet;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use UnexpectedValueException as PacketDecodeException;
use jasonwynn10\beacon\inventory\action\CustomInventoryAction;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;

/**
 * Class InventoryTransactionPacketV2
 * @package jasonwynn10\beacon\packet
 */
class InventoryTransactionPacketV2 extends InventoryTransactionPacket {
    /**
     *
     */
    public const NETWORK_ID = ProtocolInfo::INVENTORY_TRANSACTION_PACKET;

    /**
     *
     */
    public const TYPE_NORMAL = 0;
    /**
     *
     */
    public const TYPE_MISMATCH = 1;
    /**
     *
     */
    public const TYPE_USE_ITEM = 2;
    /**
     *
     */
    public const TYPE_USE_ITEM_ON_ENTITY = 3;
    /**
     *
     */
    public const TYPE_RELEASE_ITEM = 4;

	/** @var int */
	public $requestId;
	/** @var InventoryTransactionChangedSlotsHack[] */
	public $requestChangedSlots;
	/** @var TransactionData */
	public $trData;
    /**
     * @var
     */
    public $transactionType;
    /**
     * @var
     */
    public $hasItemStackIds;

    /**
     *
     */
    protected function decodePayload() : void{
		$in = $this;
		$this->requestId = $in->readGenericTypeNetworkId();
		$this->requestChangedSlots = [];
		if($this->requestId !== 0){
			for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; ++$i){
				$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($this);
			}
		}

		$this->transactionType = $this->getUnsignedVarInt();

		$this->hasItemStackIds = $this->getBool();

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->actions[] = (new CustomInventoryAction())->read($this);
		}

		$transactionType = $in->getUnsignedVarInt();

		switch($transactionType){
			case self::TYPE_NORMAL:
				$this->trData = new NormalTransactionData();
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
		$this->trData->decode($in);
	}
}
