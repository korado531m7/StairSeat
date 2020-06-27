<?php
/*
 * This file is part of StairSeat.
 *
 *  StairSeat is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  StairSeat is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with StairSeat.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace korado531m7\StairSeat;


use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Player;

class SeatData{
    /** @var Player */
    private $player;
    /** @var Block */
    private $block;
    /** @var int */
    private $eid;

    public function __construct(Player $player, Block $block){
        $this->eid = Entity::$entityCount++;
        $this->player = $player;
        $this->block = $block;
    }

    /**
     * @return Player
     */
    public function getPlayer() : Player{
        return $this->player;
    }

    /**
     * @return Block
     */
    public function getBlock() : Block{
        return $this->block;
    }

    /**
     * @param Position $pos
     *
     * @return bool
     */
    public function equals(Position $pos) : bool{
        return ($this->block->equals($pos) && $this->block->getLevel()->getId() === $pos->getLevel()->getId());
    }

    public function stand() : void{
        $pk = new SetActorLinkPacket();
        $pk->link = new EntityLink($this->eid, $this->player->getId(), EntityLink::TYPE_REMOVE, true, true);//TODO: Check causedByRider
        $this->player->getServer()->broadcastPacket($this->player->getServer()->getOnlinePlayers(), $pk);
        $pk = new RemoveActorPacket();
        $pk->entityUniqueId = $this->eid;
        $this->player->getServer()->broadcastPacket($this->player->getServer()->getOnlinePlayers(),$pk);
        $this->player->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
    }

    /**
     * @param Player[] $target
     */
    public function seat(array $target) : void{
        $addEntity = new AddActorPacket();
        $addEntity->entityRuntimeId = $this->eid;
        $addEntity->type = AddActorPacket::LEGACY_ID_MAP_BC[Entity::CHICKEN];
        $addEntity->position = $this->block->add(0.5, 1.5, 0.5);
        $flags = (1 << Entity::DATA_FLAG_IMMOBILE | 1 << Entity::DATA_FLAG_SILENT | 1 << Entity::DATA_FLAG_INVISIBLE);
        $addEntity->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags]];
        $setEntity = new SetActorLinkPacket();
        $setEntity->link = new EntityLink($this->eid, $this->player->getId(), EntityLink::TYPE_RIDER, true, true);//TODO: Check causedByRider
        $this->player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
        $this->player->getServer()->broadcastPacket($target, $addEntity);
        $this->player->getServer()->broadcastPacket($target, $setEntity);
    }
}