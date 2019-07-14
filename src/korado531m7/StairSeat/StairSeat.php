<?php
namespace korado531m7\StairSeat;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\block\Stair;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class StairSeat extends PluginBase{
    public $sit = [];
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        @mkdir($this->getDataFolder(), 0744, true);
        $this->saveResource('config.yml', false);
        $this->config = new Config($this->getDataFolder().'config.yml', Config::YAML);
    }
    
    public function isStairBlock(Block $block) : bool{
        return $block instanceof Stair && $block->getDamage() <= 3;
    }
    
    public function isAllowedUnderBlock(Block $block) : bool{
        $bk = $this->config->get('allow-block-under-id');
        $isBool = is_bool($bk);
        return $isBool && $bk ? true : ($isBool ? false : $block->getLevel()->getBlock($block->down())->getId() === $bk);
    }
    
    public function isAllowedStair(Block $block) : bool{
        $id = $this->config->get('disable-block-ids');
        if($id === false){
            return true;
        }else{
            foreach(explode(',', $id) as $i){
                if($block->getId() == trim($i)) return false;
            }
        }
        return true;
    }
    
    public function canUseWorld(Level $level) : bool{
        $world = $this->config->get('apply-world');
        if($world === true){
            return true;
        }else{
            foreach(explode(',', $world) as $w){
                if(strtolower($level->getName()) === strtolower(trim($w))) return true;
            }
        }
        return false;
    }
    
    public function isAllowedHighHeight(Player $player, Vector3 $pos) : bool{
        return $this->config->get('allow-seat-high-height') ? true : $player->y - $pos->y >= 0;
    }
    
    public function canSit(Player $player, Block $block) : bool{
        return $this->isStairBlock($block) && 
                $this->canUseWorld($player->getLevel()) && 
                $this->isAllowedHighHeight($player, $block->asVector3()) && 
                $this->isAllowedUnderBlock($block) &&
                $this->isAllowedStair($block);
    }
    
    public function isUsingSeat(Vector3 $pos) : ?Player{
        foreach($this->sit as $name => $data){
            if($pos->equals($data[1])){
                $player = $this->getServer()->getPlayer($name);
                return $player;
            }
        }
        return null;
    }
    
    public function getSitData(Player $player, int $type = 0){
        return $this->sit[$player->getName()][$type];
    }
    
    public function setSitPlayerId(Player $player, int $id, Vector3 $pos) : void{
        $this->sit[$player->getName()] = [$id, $pos];
    }
    
    public function isSitting(Player $player) : bool{
        return array_key_exists($player->getName(), $this->sit);
    }
    
    public function unsetSitting(Player $player){
        $id = $this->getSitData($player);
        $pk = new SetActorLinkPacket();
        $entLink = new EntityLink();
        $entLink->fromEntityUniqueId = $id;
        $entLink->toEntityUniqueId = $player->getId();
        $entLink->immediate = true;
        $entLink->type = EntityLink::TYPE_REMOVE;
        $pk->link = $entLink;
        $this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(), $pk);
        $pk = new RemoveActorPacket();
        $pk->entityUniqueId = $id;
        $this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(),$pk);
        $player->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
        unset($this->sit[$player->getName()]);
    }
    
    public function setSitting(Player $player, Vector3 $pos, int $id, ?Player $specific = null){
        $addEntity = new AddActorPacket();
        $addEntity->entityRuntimeId = $id;
        $addEntity->type = 10;
        $addEntity->position = $pos->add(0.5, 1.5, 0.5);
        $flags = (1 << Entity::DATA_FLAG_IMMOBILE | 1 << Entity::DATA_FLAG_SILENT | 1 << Entity::DATA_FLAG_INVISIBLE);
        $addEntity->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags]];
        $setEntity = new SetActorLinkPacket();
        $entLink = new EntityLink();
        $entLink->fromEntityUniqueId = $id;
        $entLink->toEntityUniqueId = $player->getId();
        $entLink->immediate = true;
        $entLink->type = EntityLink::TYPE_RIDER;
        $setEntity->link = $entLink;
        if($specific){
            $specific->dataPacket($addEntity);
            $specific->dataPacket($setEntity);
        }else{
            $player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
            $this->setSitPlayerId($player, $id, $pos->floor());
            $this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(), $addEntity);
            $this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(), $setEntity);
        }
    }
}