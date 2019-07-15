<?php
namespace korado531m7\StairSeat;

use pocketmine\scheduler\Task;
use pocketmine\Player;

class SendTask extends Task{
    public function __construct(Player $player,array $data,StairSeat $instance){
        $this->who = $player;
        $this->data = $data;
        $this->instance = $instance;
    }
    
    public function onRun(int $tick) : void{
        foreach($this->data as $name => $datum){
            $player = $this->instance->getServer()->getPlayerExact($name);
            if($player === null || !$this->who->isOnline()) continue;
            $this->instance->setSitting($player, $datum[1], $datum[0], $this->who);
        }
    }
}