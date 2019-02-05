<?php
namespace korado531m7\StairSeat;

use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;

class EventListener implements Listener{
    private $instance;
    
    public function __construct(StairSeat $instance){
        $this->instance = $instance;
    }
    
    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        if($this->instance->isSitting($player)){
            $this->instance->unsetSitting($player);
        }
    }
    
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(!$this->instance->isSitting($player) && $this->instance->canSit($player, $block)){
            if($usePlayer = $this->instance->isUsingSeat($block->floor())){
                $player->sendMessage(str_replace(['@p','@b'],[$usePlayer->getName(), $block->getName()],$this->instance->config->get('tryto-sit-already-inuse')));
            }else{
                $eid = Entity::$entityCount++;
                $this->instance->setSitting($player, $block->asVector3(), $eid);
                $player->sendTip(str_replace('@b',$block->getName(),$this->instance->config->get('send-tip-when-sit')));
            }
        }
    }
    
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        //Can't apply without delaying that's why using delayed task
        if(count($this->instance->sit) >= 1) $this->instance->getScheduler()->scheduleDelayedTask(new SendTask($player, $this->instance->sit, $this->instance), 30);
    }
    
    public function onBreak(BlockBreakEvent $event){
        $block = $event->getBlock();
        if($this->instance->isStairBlock($block) && ($usingPlayer = $this->instance->isUsingSeat($block->floor()))){
            $this->instance->unsetSitting($usingPlayer);
        }
    }
    
    public function onLeave(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if($packet instanceof InteractPacket && $this->instance->isSitting($player) && $packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
            $this->instance->unsetSitting($player);
        }
    }
}