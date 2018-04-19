<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;

class WoodenPressurePlate extends Transparent{

	protected $id = self::WOODEN_PRESSURE_PLATE;
	
	protected $lastInteractWithEntity = -1;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Wooden Pressure Plate";
	}

	public function canBeActivated(){
		return true;
	}

	public function getHardness(){
		return 2;
	}
	
	public function canBeFlowedInto(){
		return false;
	}

	public function getToolType(){
		return Tool::TYPE_AXE;
	}

	public function getDrops(Item $item){
		return [
			[Item::WOODEN_PRESSURE_PLATE, 0, 1],
		];
	}
	
	public function isActive() {
		return $this->meta > 0;
	}
	
	public function hasEntityCollision() {
		return true;
	}
	
	public function onEntityCollide(Entity $entity) {
		$this->lastInteractWithEntity = microtime(true);
		if ($this->meta == 0) {
			$this->meta = 1;
			$this->level->setBlock($this, $this, true, true);
			$this->level->scheduleUpdate($this, 20);
		}
	}
	
	public function onUpdate($type) {
		if ($type == Level::BLOCK_UPDATE_SCHEDULED) {
			$now = microtime(true);
			if ($this->meta > 0 && ($this->lastInteractWithEntity < 0 || $now - $this->lastInteractWithEntity >= 1)) {
				$this->lastInteractWithEntity = -1;
				$this->meta = 0;
				$this->level->setBlock($this, $this, true, true);
			} else {
				$this->level->scheduleUpdate($this, 20);
			}
		}
	}
	
	public function updateNeighbours() {
		static $offsets = [
			[0, -1, 0],
			[1, 0, 0],
			[-1, 0, 0],
			[0, 0, 1],
			[0, 0, -1],
		];
		$pluginManager = Server::getInstance()->getPluginManager();
		$tmpVector = new Vector3();
		foreach ($offsets as $offset) {
			$tmpVector->setComponents($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			$block = $this->level->getBlock($tmpVector);
			// block beneath is solid or not beneath and redstone wire
			if (($offset[1] == -1 && $block instanceof Solid) || ($offset[1] != -1 && $block instanceof RedstoneWire)) {
				$ev = new BlockUpdateEvent($block);
				$pluginManager->callEvent($ev);
				if(!$ev->isCancelled()){
					$ev->getBlock()->onUpdate(Level::BLOCK_UPDATE_NORMAL);
				}
			}
		}
	}
}