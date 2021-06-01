<?php

    namespace nexuscore\task\effect;


    use pocketmine\plugin\Plugin;
    use pocketmine\scheduler\Task;
    use pocketmine\entity\Effect;
    use pocketmine\entity\EffectInstance;

    class nightvisiontask extends Task
    {
        private $plugin;

        public function __construct(Plugin $plugin)
        {
            $this->plugin = $plugin;
        }

        public function onRun(int $currentTick)
        {
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                $armorinventory = $player->getArmorInventory();
                if($armorinventory===null)       continue;
                if($armorinventory->getHelmet()->getCustomName()==="太古の鎧(頭)NIGHTVISION"){
                    $player->addEffect(new EffectInstance(Effect::getEffect(16), 400, 255, false));
                }
            }
            
        }
    }