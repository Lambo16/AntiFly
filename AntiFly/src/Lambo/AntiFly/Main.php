<?php

namespace Lambo\AntiFly;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;

class Main extends PluginBase implements Listener{

    private $players=array();

    public function onEnable(){
        MainLogger::getLogger()->info(TextFormat::LIGHT_PURPLE."AntiFly loaded.");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->tempbans = new Config("tempbans.yml",Config::YAML,array("temp-bans"=>array()));
        $this->tempbansar = $this->tempbans->getAll();
    }

    public function onDisable(){
        MainLogger::getLogger()->info(TextFormat::LIGHT_PURPLE."AntiFly disabled.");
    }

    /**
     * @param PlayerJoinEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled false
     */
    public function onJoin(PlayerJoinEvent $event){
        $this->players[$event->getPlayer()->getName()] = 0;
        if(isset($this->tempbansar["temp-bans"][$event->getPlayer()->getAddress()])){
            $event->getPlayer()->kick("Banned for another ".($this->tempbansar["temp-bans"][$event->getPlayer()->getAddress()]["date-banned"] - (time() - $this->tempbansar["temp-bans"][$event->getPlayer()->getAddress()]["time"]))." seconds.\nReason: ".$this->tempbansar["temp-bans"][$event->getPlayer()->getAddress()]["reason"]."\nIf you believe this to be an error, please contact us\nat our email address admin@legionpvp.eu");
            if(!in_array($event->getPlayer()->getName(),$this->tempbansar["temp-bans"][$event->getPlayer()->getAddress()]["players"])){
                array_push($this->tempbansar["temp-bans"][$event->getPlayer()->getAddress()]["players"],$event->getPlayer()->getName());
                $this->tempbans->set("temp-bans",$this->tempbansar["temp-bans"]);
                $this->tempbans->save();
            }
        }
    }

    /**
     * @param EntityMoveEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled false
     */
    public function onMove(EntityMoveEvent $event){
        if($event->getEntity() instanceof Player and $event->getEntity()->getGamemode() !== 1 and !$event->getEntity()->isOp()){
            $tempbans = $this->tempbansar;
            $player = $event->getEntity();
            $block = $event->getEntity()->getLevel()->getBlock(new Vector3($player->getFloorX(),$player->getFloorY()-1,$player->getFloorZ()));
            if($block->getID() == 0){
                if(!isset($this->players[$player->getName()])) $this->players[$player->getName()] = 0;
                $this->players[$player->getName()]++;
                if($this->players[$player->getName()] >= 90){
                    $this->players[$player->getName()] = 0;
                    $this->set($player,"reports",($this->get($player,"reports")+1));
                    if($this->get($player,"reports") == 2){
                        $player->kick("You have been kicked for suspicious movement.");
                    }else
                    if($this->get($player,"reports") == 4){
                        $tempbans["temp-bans"][$player->getAddress()]["date-banned"] = time();
                        $tempbans["temp-bans"][$player->getAddress()]["time"] = 60 * 60;
                        $tempbans["temp-bans"][$player->getAddress()]["reason"] = "Suspicious movement";
                        $tempbans["temp-bans"][$player->getAddress()]["players"] = array($player->getName());
                        $this->tempbans->set("temp-bans",$tempbans["temp-bans"]);
                        $this->tempbans->save();
                        $this->tempbansar = $tempbans;
                        $player->kick("You have been banned for ".$tempbans["temp-bans"][$player->getAddress()]["time"]." seconds.\nReason: Suspicious movement\nIf you believe this to be an error, please contact us.");
                    }
                }
            }else
            if($this->players[$player->getName()] > 0) $this->players[$player->getName()] = 0;
        }
    }
}
