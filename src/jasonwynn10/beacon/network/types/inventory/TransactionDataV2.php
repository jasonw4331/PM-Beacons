<?php
declare(strict_types=1);
namespace jasonwynn10\beacon\network\types\inventory;

use pocketmine\network\mcpe\NetworkBinaryStream as PacketSerializer;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\utils\BinaryDataException;
use UnexpectedValueException as PacketDecodeException;

abstract class TransactionDataV2{
	/** @var NetworkInventoryAction[] */
	protected $actions = [];

	/**
	 * @return NetworkInventoryAction[]
	 */
	final public function getActions() : array{
		return $this->actions;
	}

	abstract public function getTypeId() : int;

	/**
	 * @throws BinaryDataException
	 * @throws PacketDecodeException
	 */
	public function decode(PacketSerializer $stream) : void{
		$actionCount = $stream->getUnsignedVarInt();
		for($i = 0; $i < $actionCount; ++$i){
			$this->actions[] = (new NetworkInventoryActionV2())->read($stream);
		}
		$this->decodeData($stream);
	}

	/**
	 * @throws BinaryDataException
	 * @throws PacketDecodeException
	 */
	abstract protected function decodeData(PacketSerializer $stream) : void;

	public function encode(PacketSerializer $stream) : void{
		$stream->putUnsignedVarInt(count($this->actions));
		foreach($this->actions as $action){
			$action->write($stream);
		}
		$this->encodeData($stream);
	}

	abstract protected function encodeData(PacketSerializer $stream) : void;
}