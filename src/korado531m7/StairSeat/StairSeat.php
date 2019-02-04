<?php
namespace korado531m7\StairSeat;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\block\Stair;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class StairSeat extends PluginBase implements Listener{
    private $sit = [];
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder(), 0744, true);
        $this->saveResource('config.yml', false);
        $this->config = new Config($this->getDataFolder().'config.yml', Config::YAML);
    }
    
    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        if($this->isSitting($player)){
            $this->unsetSitting($player);
        }
    }
    
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        if(!$this->isSitting($player)){
            $block = $event->getBlock();
            if($this->isStairBlock($block)){
                if($usePlayer = $this->isUsingSeat($block->floor())){
                    $player->sendMessage(str_replace(['@p','@b'],[$usePlayer->getName(), $block->getName()],$this->config->get('tryto-sit-already-inuse')));
                }else{
                    $eid = Entity::$entityCount++;
                    $this->setSitting($player, $block->asVector3(), $eid);
                    $player->sendTip(str_replace('@b',$block->getName(),$this->config->get('send-tip-when-sit')));
                }
            }
        }
    }
    
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        //Can't apply without delaying that's why using delayed task
        if(count($this->sit) >= 1) $this->getScheduler()->scheduleDelayedTask(new SendTask($player, $this->sit, $this), 30);
    }
    
    public function onBreak(BlockBreakEvent $event){
        $block = $event->getBlock();
        if($this->isStairBlock($block) && ($usingPlayer = $this->isUsingSeat($block->floor()))){
            $this->unsetSitting($usingPlayer);
        }
    }
    
    //NOTE: I want to call such a related packet or event but it doesn't work so i use playermoveevent instead.
    public function onJump(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if($this->isSitting($player) && $player->floor()->equals($this->getSitData($player, 1)) && round($event->getFrom()->getY(), 1) !== round($event->getTo()->getY(), 1)){
            $this->unsetSitting($player);
        }
    }
    
    private function isStairBlock(Block $block) : bool{
        return $block instanceof Stair;
    }
    
    private function isUsingSeat(Vector3 $pos) : ?Player{
        foreach($this->sit as $name => $data){
            if($pos->equals($data[1])){
                $player = $this->getServer()->getPlayer($name);
                return $player;
            }
        }
        return null;
    }
    
    private function getSitData(Player $player, int $type = 0){
        return $this->sit[$player->getName()][$type];
    }
    
    private function setSitPlayerId(Player $player, int $id, Vector3 $pos) : void{
        $this->sit[$player->getName()] = [$id, $pos];
    }
    
    private function isSitting(Player $player) : bool{
        return array_key_exists($player->getName(), $this->sit);
    }
    
    private function unsetSitting(Player $player){
        $id = $this->getSitData($player);
        $pk = new SetEntityLinkPacket();
        $entLink = new EntityLink();
        $entLink->fromEntityUniqueId = $id;
        $entLink->toEntityUniqueId = $player->getId();
        $entLink->immediate = true;
        $entLink->type = EntityLink::TYPE_REMOVE;
        $pk->link = $entLink;
        $this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(), $pk);
        $pk = new RemoveEntityPacket();
        $pk->entityUniqueId = $id;
        $this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(),$pk);
        $player->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
        unset($this->sit[$player->getName()]);
    }
    
    public function setSitting(Player $player, Vector3 $pos, int $id, ?Player $specific = null){
        $addEntity = new AddEntityPacket();
        $addEntity->entityRuntimeId = $id;
        $addEntity->type = 10;
        $addEntity->position = $pos->add(0.5, 1.5, 0.5);
        $flags = (1 << Entity::DATA_FLAG_IMMOBILE | 1 << Entity::DATA_FLAG_SILENT | 1 << Entity::DATA_FLAG_INVISIBLE);
        $addEntity->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags]];
        $setEntity = new SetEntityLinkPacket();
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