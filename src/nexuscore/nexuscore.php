<?php

namespace nexuscore;

/* PluginBase */
use pocketmine\plugin\PluginBase;

/* Server */
use pocketmine\Server;

/* Player */
use pocketmine\player\Player;

/* Utils */
use pocketmine\utils\Config;

/* TaskScheduler */
use pocketmine\scheduler\ClosureTask;

/* Command */
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

/* Entity */
use pocketmine\entity\Entity;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\EntityDataHelper;

/* Entity(effect) */
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;

/* Item */
use pocketmine\item\Item;

/* Level */
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\entity\Location;

/* Event(entity) */
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

/* Event(player) */
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerChatEvent;

/* Event(server) */
use pocketmine\event\server\DataPacketSendEvent;

/* Network */
use pocketmine\network\mcpe\protocol\DisconnectPacket;

/* NBT */
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;

/* math */
use pocketmine\math\Vector3;


/* nexuscore special arrows */
use nexuscore\arrow\AntiGravityArrow;
use nexuscore\arrow\DoubleGravityArrow;
use nexuscore\arrow\NewArrow;
use nexuscore\arrow\HomingArrow;
use nexuscore\arrow\HiPowerHomingArrow;
use nexuscore\arrow\HomingMissile;
use nexuscore\arrow\HomingMissileArrow;

/* nexucore tasks */
use nexuscore\task\entitykilltask;
use nexuscore\task\broadcasttask;
use nexuscore\task\checktweettask;
use nexuscore\task\maxentitystask;
use nexuscore\task\EffectTask;
use nexuscore\task\effect\shieldrechargetask;

/* nexuscore FormAPI */
/* https://github.com/Yahir-AR/FormAPI-PMMP.git */
use nexuscore\FormAPI\window\SimpleWindowForm;
use nexuscore\FormAPI\window\CustomWindowForm;
use nexuscore\FormAPI\response\PlayerWindowResponse;

/* nexuscore wwarp */
/* https://github.com/lovetwice1012/worldwarp.git */
use nexuscore\worldwarp\WMAPI;
use nexuscore\worldwarp\CustomForm;

/* nexuscore PlayerInfoScoreBoard(remix) */
/* https://github.com/lovetwice1012/nexusPISB.git */
/* https://github.com/yurisi0212/PlayerInfoScoreBoard.git */
use nexuscore\PISB\Task\Sendtask;
use nexuscore\PISB\Command\johoCommand;

/* EconomyAPI(pm4) */
/* https://github.com/onebone/EconomyS.git */
/* https://github.com/lovetwice1012/EconomyAPI-PM4.git */
use onebone\economyapi\EconomyAPI;

class nexuscore extends PluginBase implements Listener
{
    public static $defaultwipe;
    public static $nextwipe;
    public static $itemconfig;
    public static $shieldconfig;
    public $wipeconfig;
    private $cancelGuard = false;
    public $rewardconfig;
    public $dayconfig;
    public $tppconfig;
    public $preloginconfig;
    public $data;
    public $plugin;
    public $Main;
    private static $main;

    public function onEnable() :void
    {
        self::$main=$this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->wipeconfig = new Config($this->getDataFolder() . "wipe.yml", Config::YAML);
        $this->preloginconfig = new Config($this->getDataFolder() . "prelogin.yml", Config::YAML);
        /*$this->rewardconfig = new Config($this->getDataFolder() . "Reward.json", Config::JSON, [
            "can_duplicate" => true,
            "Reward_economy" => [
                1000,
                10000,
            ],
            "Reward_items" => [
                [
                    [
                        "id" => 1,
                        "damage" => 0,
                        "count" => 64
                    ],
                    [
                        "id" => 2,
                        "damage" => 0,
                        "count" => 64
                    ]
                ],
                [
                    [
                        "id" => 1,
                        "damage" => 0,
                        "count" => 64
                    ],
                    [
                        "id" => 2,
                        "damage" => 0,
                        "count" => 64
                    ]
                ]
            ],
            "Reward_table" => [
                "???" => 0,
                "???" => -1,
                "???" => 1,
                "???" => 1,
                "???" => 1,
                "???" => 1,
                "???" => -1
            ]
        ]);*/

        $this->saveResource("Reward.json");
        $this->rewardconfig = new Config($this->getDataFolder() . "Reward.json", Config::JSON);
        $this->rewardconfig->enableJsonOption(JSON_UNESCAPED_UNICODE);
        //$this->rewardconfig->save();

        $this->dayconfig = new Config($this->getDataFolder() . "lastday.yml", Config::YAML);
        $this->tppconfig = new Config($this->getDataFolder() . "tpp.yml", Config::YAML);
        self::$itemconfig = new Config($this->getDataFolder() . "ItemReward.json", Config::JSON);
        self::$itemconfig->enableJsonOption(JSON_UNESCAPED_UNICODE);

        if (!$this->wipeconfig->exists("wipe")) {
            $this->wipeconfig->set("wipe", 30);
            $this->wipeconfig->save();
        }
        nexuscore::$nextwipe = $this->wipeconfig->get("wipe");
        nexuscore::$defaultwipe = $this->wipeconfig->get("wipe");
        self::$shieldconfig = new Config($this->getDataFolder() . "shield.json", Config::JSON);
        EntityFactory::getInstance()->register(NewArrow::class, function(World $world, CompoundTag $nbt):NewArrow{ return new NewArrow(EntityDataHelper::parseLocation($nbt, $world) ,$nbt);}, ['NewArrow', 'minecraft:newarrow']);
        EntityFactory::getInstance()->register(AntiGravityArrow::class, function(World $world, CompoundTag $nbt):AntiGravityArrow{ return new AntiGravityArrow(EntityDataHelper::parseLocation($nbt, $world) ,$nbt);}, ['AntiGravityArrow', 'minecraft:antigravityarrow']);
        EntityFactory::getInstance()->register(DoubleGravityArrow::class, function(World $world, CompoundTag $nbt):DoubleGravityArrow{ return new DoubleGravityArrow(EntityDataHelper::parseLocation($nbt, $world),$nbt);}, ['DoubleGravityArrow', 'minecraft:doublegravityarrow']);
        EntityFactory::getInstance()->register(HomingArrow::class, function(World $world, CompoundTag $nbt):HomingArrow{ return new HomingArrow(EntityDataHelper::parseLocation($nbt, $world) ,$nbt);}, ['HomingArrow', 'minecraft:homingarrow']);
        EntityFactory::getInstance()->register(HiPowerHomingArrow::class, function(World $world, CompoundTag $nbt):HiPowerHomingArrow{ return new HiPowerHomingArrow(EntityDataHelper::parseLocation($nbt, $world) ,$nbt);}, ['HiPowerHomingArrow', 'minecraft:hipowerhomingarrow']); 
        EntityFactory::getInstance()->register(HomingMissileArrow::class, function(World $world, CompoundTag $nbt):HomingMissileArrow{ return new HomingMissileArrow(EntityDataHelper::parseLocation($nbt, $world) ,$nbt);}, ['HomingMissileArrow', 'minecraft:homingmissilearrow']); 
        $this->getScheduler()->scheduleRepeatingTask(new entitykilltask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new EffectTask($this), 10);
	    $this->getScheduler()->scheduleRepeatingTask(new maxentitystask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new shieldrechargetask($this), 10);
        $this->getScheduler()->scheduleRepeatingTask(new broadcasttask($this), 20 * 60 * 5);
        $this->getScheduler()->scheduleRepeatingTask(new checktweettask($this, $this->rewardconfig, $this->dayconfig), 36000);//30???*20(1800*20)
        $this->getScheduler()->scheduleRepeatingTask(new Sendtask(), 5);
        //$this->getServer()->getCommandMap()->register($this->getName(), new johoCommand());
    }

    function onEntityShootBow(EntityShootBowEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Player) return;
        
        $nbt = $this->createBaseNBT(
            $entity->getLocation()->add(0, 1.8, 0),
            $entity->getDirectionVector(),
            ($entity->getLocation()->yaw > 180 ? 360 : 0) - $entity->getLocation()->yaw,
            -$entity->getLocation()->pitch
        );
        
		
        $diff = $entity->getItemUseDuration();
        $p = $diff / 20;
        $baseForce = min((($p ** 2) + $p * 2) / 3, 1);
        
        if ($event->getBow()->getCustomName() === 'POWER-BOW') {
            $diff = $entity->getItemUseDuration();
            $p = $diff / 20;
            $baseForce = min((($p ** 2) + $p * 2) ** 3, 1);
            $arrow = new NewArrow(Location::fromObject(
            			$entity->getEyePos(),
            			$entity->getWorld(),
            			($entity->getLocation()->yaw > 180 ? 360 : 0) - $entity->getLocation()->yaw,
            			- $entity->getLocation()->pitch
            		) ,$nbt ,$entity ,false ,$entity->getWorld());
            $event->setProjectile($arrow);
        } else if ($event->getBow()->getCustomName() === 'ANTIGRAVITY-BOW') {
            $arrow = new AntiGravityArrow(Location::fromObject(
                        			$entity->getEyePos(),
                        			$entity->getWorld(),
                        			($entity->getLocation()->yaw > 180 ? 360 : 0) - $entity->getLocation()->yaw,
                        			- $entity->getLocation()->pitch
                        		) ,$nbt ,$entity ,false ,$entity->getWorld());
            $event->setProjectile($arrow);
        } else if ($event->getBow()->getCustomName() === 'DOUBLEGRAVITY-BOW') {
            $arrow = new DoubleGravityArrow(Location::fromObject(
                        			$entity->getEyePos(),
                        			$entity->getWorld(),
                        			($entity->getLocation()->yaw > 180 ? 360 : 0) - $entity->getLocation()->yaw,
                        			- $entity->getLocation()->pitch
                        		) ,$nbt ,$entity ,false ,$entity->getWorld());
            $event->setProjectile($arrow);
        } else if ($event->getBow()->getCustomName() === 'HOMING-BOW') {
            $arrow = new HomingArrow(Location::fromObject(
                        			$entity->getEyePos(),
                        			$entity->getWorld(),
                        			($entity->getLocation()->yaw > 180 ? 360 : 0) - $entity->getLocation()->yaw,
                        			- $entity->getLocation()->pitch
                        		) ,$nbt ,$entity ,false ,$entity->getWorld());
            $event->setProjectile($arrow);
        } else if ($event->getBow()->getCustomName() === 'HI-POWER-HOMING-BOW') {
            $arrow = new HiPowerHomingArrow(Location::fromObject(
                        			$entity->getEyePos(),
                        			$entity->getWorld(),
                        			($entity->getLocation()->yaw > 180 ? 360 : 0) - $entity->getLocation()->yaw,
                        			- $entity->getLocation()->pitch
                        		) ,$nbt ,$entity ,false ,$entity->getWorld());
            $event->setProjectile($arrow);
        } else if ($event->getBow()->getCustomName() === 'HOMING-MISSILE-BOW') {
            $arrow = new HomingMissileArrow(Location::fromObject(
                        			$entity->getEyePos(),
                        			$entity->getWorld(),
                        			($entity->getLocation()->yaw > 180 ? 360 : 0) - $entity->getLocation()->yaw,
                        			- $entity->getLocation()->pitch
                        		) ,$nbt ,$entity ,false ,$entity->getWorld());
            $event->setProjectile($arrow);
        }
    
    }

/*
  public function onShootBow(\pocketmine\event\entity\EntityShootBowEvent $event) : void{
    $entity = $event->getEntity();
    $bow = $event->getBow();
    $projectile = $event->getProjectile();

    if($entity instanceof Player){
      if($bow->getCustomName() === "TP-BOW"){
        $projectile->namedtag->setByte("TeleportBow", 1);
      }
    }
  }

  public function onHitBlock(\pocketmine\event\entity\ProjectileHitBlockEvent $event) : void{
    $projectile = $event->getEntity();

    
if($projectile->namedtag->offsetExists("TeleportBow")){
  if($projectile->namedtag->getByte("TeleportBow") === 1){
    $owner = $projectile->getWorld()->getEntity($projectile->getOwningEntityId());
     if($owner instanceof Player) $owner->teleport($event->getRayTraceResult()->getHitVector());
  }
}
  }
*/
  public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch (strtolower($command->getName())) {

            case "rand":
                if ($sender instanceof Player) {
                    $player = $sender->getServer()->getPlayerByPrefix($sender->getName());
                    $user = $player->getName();
                    if (!isset($args[1]) || !isset($args[1])) return false;
                    $max = $args[1];
                    $min = $args[0];
                    $a = random_int($min, $max);
                    if (isset($args[2])) {
                        if ($args[2] == true) {
                            $this->getServer()->broadcastTip("??e[rand]??a " . $user . " ???" . $a . "?????????????????????");
                        } else {
                            $sender->sendTip("??e[rand]??a ????????f : ??l??b" . $a . "??r");
                        }
                    } else {
                        $sender->sendTip("??e[rand]??a ????????f : ??l??b" . $a . "??r");
                    }
                } else {
                    $this->getLogger()->info("??e[rand] ??c????????????????????????????????????????????????");
                }
                break;
                case "mywarp":
                case "myw":
                if ($sender instanceof Player) {
                    $player = $sender->getServer()->getPlayerByPrefix($sender->getName());
                    $window = new SimpleWindowForm("mywarp menu", "??5Mywarp menu", "??????????????????????????????????????????");
                    $window->addButton("warp", "???????????????");
                    $window->addButton("add", "??????????????????????????????");
                    $window->addButton("delete", "??????????????????????????????");            
                    $window->showTo($player);
                }
                break;
                case "tpp":
                if ($sender instanceof Player) {
                    $name = $sender->getName();
                    //var_dump($args);
                    if(empty($args)){
                    $sender->sendMessage("?????????????????????????????????????????????????????????????????????????????????");
                    return true;
                    }
                    $teleportplayer = Server::getInstance()->getPlayerByPrefix($args[0]);
                    $teleportplayername = $teleportplayer->getName();

                    if($teleportplayer instanceof Player){
                        if($this->tppconfig->exists($name)){
                            $sender->sendMessage("??????????????????????????????????????????????????????????????????????????????????????????????????????????????????");
                            return true;
                        }
                        if($this->tppconfig->exists($teleportplayername)){
                            $sender->sendMessage("???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????");
                            return true;
                        }
                        $getall = $this->tppconfig->getAll();
                        $alreadysend = false;
                        foreach($getall as $key => $value){
                            //var_dump($value);
                            if($value == $name){
                                $alreadysend = true;
                                
                                if(!(Server::getInstance()->getPlayerByPrefix($key) instanceof Player)) {
                                    $this->tppconfig->remove($key);
                                    $this->tppconfig->save();
                                }

                            }
                        }
                        if($alreadysend){
                            $sender->sendMessage("????????????????????????????????????".$key."???????????????????????????????????????????????????????????????????????????????????????????????????????????????");
                            return true;
                        }
                        $this->tppconfig->set($teleportplayer->getName(), $name);
                        $this->tppconfig->save();
                        $sender->sendMessage("????????????????????????????????????????????????????????????????????????????????????");
                        $teleportplayer->sendMessage("??5".$name."?????????????????????????????????????????????????????????????????????????????????????????????????????????????????????accept-tpp??????????????????????????????deny-tpp?????????????????????????????????");
                        return true;
                    }
                    $sender->sendMessage("??????????????????????????????????????????");
                    return true;
                }
                break;
                case "wwarp":
                if(!$sender instanceof Player) {
                    $sender->sendMessage("????????????????????????????????????????????????");
                    return true;
                }
                $customForm = new CustomForm("world warp");
                $customForm->addLabel("[?????????????????????]??????????????????????????????????????????????????????");
                $customForm->addDropdown("Level", WMAPI::getAllLevels());
                $sender->getServer()->getPlayerByPrefix($sender->getName())->sendForm($customForm); 
                return true;
                break;
                case "gam":
                    $players = Server::getInstance()->getOnlinePlayers();
                    foreach ($players as $p){
                        EconomyAPI::getInstance()->addMoney($p, $args[0] ?: 1500);
                    }
        }
        return true;
    }
    public static function handleCustomFormResponse(Player $player, $data, CustomForm $form) {
        if($data === null){
            return;
	    }
	
	    $levelsname = WMAPI::getAllLevels()[$data[1]];
	    if(!Server::getInstance()->getWorldManager()->isWorldGenerated($levelsname)) {
            $player->sendMessage("??????????????????????????????????????????");
            return;
        }

        if(!Server::getInstance()->getWorldManager()->isWorldLoaded($levelsname)) {
            Server::getInstance()->getWorldManager()->loadWorld($levelsname);
        }

        $level = Server::getInstance()->getWorldManager()->getWorldByName($levelsname);

        $player->teleport($level->getSafeSpawn());
        $player->sendMessage($levelsname."????????????????????????");
        return;
        }

    public function onChat(PlayerChatEvent $event){
        $message = $event->getMessage();
        $player = $event->getPlayer();
        $name = $player->getName();
        if($message == "accept-tpp"){
            if($this->tppconfig->exists($name)){
                $teleportplayername = $this->tppconfig->get($name);
                $teleportplayer = Server::getInstance()->getPlayerByPrefix($teleportplayername);
                if($teleportplayer instanceof Player){
                    $world = $player->getWorld();
                    if($world->getFolderName() === "creative"){
                    $this->tppconfig->remove($name);
                    $this->tppconfig->save();
                    $player->sendMessage($teleportplayername."??????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????");
                    $teleportplayer->sendMessage($name."?????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????");
                    return;
                    }
                    $teleportplayer->teleport(new Position($player->getLocation()->getX(), $player->getLocation()->getY(), $player->getLocation()->getZ(), $world));
                    $this->tppconfig->remove($name);
                    $this->tppconfig->save();
                    $player->sendMessage($teleportplayername."??????????????????????????????????????????????????????");
                    $teleportplayer->sendMessage($name."????????????????????????????????????????????????????????????????????????????????????????????????");
                    $event->cancel(true);
                }
            }
        }
        if($message == "deny-tpp"){
            if($this->tppconfig->exists($name)){
                $teleportplayername = $this->tppconfig->get($name);
                $teleportplayer = Server::getInstance()->getPlayerByPrefix($teleportplayername);

                    $this->tppconfig->remove($name);
                    $this->tppconfig->save();
                    $player->sendMessage($teleportplayername."??????????????????????????????????????????????????????");
                if($teleportplayer instanceof Player){
                    $teleportplayer->sendMessage($name."??????????????????????????????????????????????????????????????????");
                }
                $event->cancel(true);
            }
        }
    }

    public function onResponse(PlayerWindowResponse $event){
        $player = $event->getPlayer();
        $form = $event->getForm();

        
        if($form->isClosed()) {
            return;
        }

        if(($form instanceof SimpleWindowForm) && $form->getName() === "mywarp menu"){
            switch($form->getClickedButton()->getText()){
                case "???????????????":
                $mywarpconfig = new Config($this->getDataFolder() . "mywarp/".$player->getName().".yml", Config::YAML);
                $window = new SimpleWindowForm("mywarp warp", "??5Mywarp menu", "????????????????????????????????????????????????");
                $datas = $mywarpconfig->getAll(true);
                foreach($datas as $data){
                    if($data !== null){
                        $window->addButton($data, $data);
                    }
                }
                $window->showTo($player);
                break;
                case "??????????????????????????????":
                $window = new CustomWindowForm("mywarp add", "??5Mywarp menu", "?????????????????????????????????");
                $window->addInput("warpname", "?????????????????????????????????????????????");
                $window->showTo($player);
                break;
                case "??????????????????????????????":
                $mywarpconfig = new Config($this->getDataFolder() . "mywarp/".$player->getName().".yml", Config::YAML);
                $window = new SimpleWindowForm("mywarp delete", "??5Mywarp menu", "?????????????????????????????????????????????");
                $datas = $mywarpconfig->getAll(true);
                foreach($datas as $data){
                    if($data !== null){
                        $window->addButton($data, $data);
                    }
                }
                $window->showTo($player);
                break;
                default:
                $player->sendMessage($form->getClickedButton()->getText());
                break;
            }
        }else if(($form instanceof CustomWindowForm ) && $form->getName() === "mywarp add"){
            $mywarpconfig = new Config($this->getDataFolder() . "mywarp/".$player->getName().".yml", Config::YAML);
            $warpname = $form->getElement("warpname")->getFinalValue();
            $mywarpconfig->set($warpname, $player->getLocation()->getX().",".$player->getLocation()->getY().",".$player->getLocation()->getZ().",".$player->getWorld()->getFolderName());
            $mywarpconfig->save();
            $player->sendMessage("?????????????????????");
        }else if(($form instanceof SimpleWindowForm) && $form->getName() === "mywarp delete"){
            $mywarpconfig = new Config($this->getDataFolder() . "mywarp/".$player->getName().".yml", Config::YAML);
            $warpname = $form->getClickedButton()->getText();
            $mywarpconfig->remove($warpname);
            $mywarpconfig->save();
            $player->sendMessage("?????????????????????");
        }else if(($form instanceof SimpleWindowForm) && $form->getName() === "mywarp warp"){
            $mywarpconfig = new Config($this->getDataFolder() . "mywarp/".$player->getName().".yml", Config::YAML);
            $warpname = $form->getClickedButton()->getText();
            $data = $mywarpconfig->get($warpname);
            $value = explode(",", $data);
            $world = Server::getInstance()->getWorldManager()->getWorldByName($value[3]);
            $player->teleport(new Position(floatval($value[0]), floatval($value[1]), floatval($value[2]), $world));
            $player->sendMessage("??????????????????????????????????????????");
        }
    }
public function onJoin(PlayerJoinEvent $event)
    {
    $player_name  = $event->getPlayer()->getName();
    if($this->preloginconfig->exists($player_name)){
    $this->preloginconfig->remove($player_name);
    $this->preloginconfig->save();
    }
        $player = $event->getPlayer();
        if(!self::$shieldconfig->exists($player->getName())){
        self::$shieldconfig->set($player->getName(),"???????????????");
        self::$shieldconfig->save();
        }
        $items = self::$itemconfig->get($player->getName(), null);
        if ($items === null||count($items) === 0) {
            return;
        }
        $error = false;
        foreach ($items as $id => $itemdata) {
            $item = Item::jsonDeserialize($itemdata);
            if (!$player->getInventory()->canAddItem($item)) {
                $error = true;
                self::$itemconfig->set($player->getName(), $items);
                self::$itemconfig->save();
                $player->sendMessage("???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????");
                break;
            }
            unset($items[$id]);
            $player->getInventory()->addItem($item);
        }
        if (!$error) {
            $player->sendMessage("SNS????????????????????????????????????????????????");
            self::$itemconfig->set($player->getName(), null);
            self::$itemconfig->save();
        }
    }

public function onDamage(EntityDamageByEntityEvent $event)
    {
        $player = $event->getEntity();
            if ($player instanceof Player) { 
                    $killer = $event->getDamager();
                    $playerN = $player->getNameTag();
                    $shield = self::$shieldconfig->get($playerN);
                    if($shield>0){
                    $shielddamage = $shield - floor($event->getFinalDamage());
                    if($shielddamage < 0) $shielddamage = 0;
                    self::$shieldconfig->set($player->getNameTag(),$shielddamage);
                    self::$shieldconfig->save();
                    $player->sendTip("??5???????????????????????????".floor($event->getFinalDamage())."??????????????????????????????...!(??????????????????:".$shielddamage.")");
                    $event->cancel();
                    return;
                    }
                    //$killerN = $killer->getName();
                    $armorinventory = $player->getArmorInventory();
                    if ($event->getFinalDamage() >= $event->getEntity()->getHealth()) {
                        $event->cancel();
                        $player->setHealth(20);
                        if($armorinventory->getChestplate()->getCustomName()!=="????????????(???)Resurrection"){
                        $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                        foreach ($this->getServer()->getOnlinePlayers() as $players) {
                            $killer_msg = /*"??4".$killerN."??5???*/"??5".$playerN."??4????????????????????????";
                            $players->sendMessage($killer_msg);
			            }
                        if($armorinventory->getChestplate()->getCustomName()==="????????????(???)Resurrection-USED"){
                            $armor = $armorinventory->getChestplate();
                            $armor->setCustomName("????????????(???)Resurrection");
                            $armorinventory->setChestPlate($armor);
                        }
                        self::$shieldconfig->set($player->getName(),"???????????????");
                        self::$shieldconfig->save();
                        }else{
                            $armor = $armorinventory->getChestplate();
                            $armor->setCustomName("????????????(???)Resurrection-USED");
                            $armorinventory->setChestPlate($armor);
                        foreach ($this->getServer()->getOnlinePlayers() as $players) {
                            $killer_msg = "??4".$playerN."??5???????????????[Resurrection]????????????????????????????????????";
                            $players->sendMessage($killer_msg);
			            }
                        
                        }
		            }      
               }
        $entity = $event->getEntity();
        if(!$entity instanceof Player) return;
        $armorinventory = $entity->getArmorInventory();
        $armorpower = 0;
        //var_dump($armorinventory->getHelmet()->getCustomName());
        if($armorinventory->getHelmet()->getCustomName()==="????????????(???)" || $armorinventory->getHelmet()->getCustomName()==="????????????(???)NIGHTVISION") $armorpower = $armorpower + 1;
        if($armorinventory->getChestplate()->getCustomName()==="????????????(???)" || $armorinventory->getChestplate()->getCustomName()==="????????????(???)Resurrection" || $armorinventory->getChestplate()->getCustomName()==="????????????(???)Resurrection-USED") $armorpower = $armorpower + 1;
        if($armorinventory->getLeggings()->getCustomName()==="????????????(???)" || $armorinventory->getLeggings()->getCustomName()==="????????????(???)HIGHJUMP") $armorpower = $armorpower + 1;
        if($armorinventory->getBoots()->getCustomName()==="????????????(???)" || $armorinventory->getBoots()->getCustomName()==="????????????(???)SPEED" || $armorinventory->getBoots()->getCustomName()==="????????????(???)SPEED-V2") $armorpower = $armorpower + 1;

        if($armorpower >= 1){
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 100, $armorpower, false));
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 100, $armorpower*2, false));
        }
        if($armorpower >= 2){
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::INVISIBILITY(), 100, 255, false));
            $entity->hidePlayer($entity);
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($entity): void {
                $entity->showPlayer($entity);
            }), 20 * 5);
        }
        if($armorpower == 4){
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), 100, $armorpower*2, false));
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 100, $armorpower, false));
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), 100, $armorpower, false));
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::ABSORPTION(), 100, $armorpower, false));
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::HEALTH_BOOST(), 100, $armorpower*3, false));
        }


        $armordefencekb = $armorpower * 20;
        if($armordefencekb == 0) $armordefencekb = 1;
        if($armordefencekb == 80) $armordefencekb = 160;
        //var_dump($armordefencekb);
        $kb = $event->getKnockBack()/$armordefencekb;
        //var_dump($event->getKnockBack());
        //var_dump($kb);
        $event->setKnockBack($kb);
    }

    public function onDisable() :void
    {
        $players = $this->getServer()->getOnlinePlayers();
        foreach ($players as $p) {
            $p->transfer("server.playnexus.online", 7777);
        }
    }

    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        $playerN = $player->getName();
        if ($event->getPlayer()->getLocation()->getY() < -5) {
            $event->getPlayer()->teleport($event->getPlayer()->getWorld()->getSafeSpawn());
            $player->setHealth(20);
            foreach ($this->getServer()->getOnlinePlayers() as $players) {
                $void_msg = "??4".$playerN."??5????????????????????????????????????????????????????????????????????????";
                $players->sendMessage($void_msg);
            }
        }
    }
/*
  public function onPacketSends(DataPacketSendEvent $event):void{
    $packet = $event->getPackets();
    if(!$packet instanceof DisconnectPacket) return;
    $player_name  = $event->get()->getName();
    if($this->preloginconfig->exists($player_name)){
    $message =  $player_name.'??????????????????????????????????????????????????????';
    $this->preloginconfig->remove($player_name);
    $this->preloginconfig->save();
    }
    if(!isset($message)) return; //$message???????????????????????????
    Server::getInstance()->broadcastMessage("??a".$message);

    }
*/
    public function onPreLogin(PlayerPreLoginEvent $event){
    $player = $event->getPlayerInfo();
    $name   = $player->getUsername();
    $this->preloginconfig->set($name,true);
    $this->preloginconfig->save();
    Server::getInstance()->broadcastMessage("??a".$name."???????????????????????????????????????");
    }
    public static function getInstance():self {
        return self::$main;
    }
 
    public function isOn(Player $player) {
        //$tag = $player->getNameTag();
        //if ($tag->offsetExists($this->getName())) if (!$tag->getInt($this->getName()) == 0) return false;
        return true;
    }
    public function createBaseNBT(Vector3 $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0) : CompoundTag{
        $tag = new CompoundTag();
        $tag->setTag("Pos",new ListTag([
            new DoubleTag($pos->x),
            new DoubleTag($pos->y),
            new DoubleTag($pos->z)
        ],
        6));
        $tag->setTag("Motion",new ListTag([
            new DoubleTag($motion !== null ? $motion->x : 0.0),
            new DoubleTag($motion !== null ? $motion->y : 0.0),
            new DoubleTag($motion !== null ? $motion->z : 0.0)
        ],
        6));
        $tag->setTag("Rotation",new ListTag([
            new FloatTag($yaw),
			new FloatTag($pitch)
        ],
        5));
		return $tag;
	}
}
