<?php

declare(strict_types=1);

namespace slapper;

use pocketmine\block\BlockFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

use slapper\entities\other\{
    SlapperBoat, SlapperFallingSand, SlapperMinecart, SlapperPrimedTNT
};
use slapper\entities\{
    SlapperBat, SlapperBlaze, SlapperCaveSpider, SlapperChicken,
    SlapperCow, SlapperCreeper, SlapperDonkey, SlapperElderGuardian,
    SlapperEnderman, SlapperEndermite, SlapperEntity, SlapperEvoker,
    SlapperGhast, SlapperGuardian, SlapperHorse, SlapperHuman,
    SlapperHusk, SlapperIronGolem, SlapperLavaSlime, SlapperLlama,
    SlapperMule, SlapperMushroomCow, SlapperOcelot, SlapperPig,
    SlapperPigZombie, SlapperPolarBear, SlapperRabbit, SlapperSheep,
    SlapperShulker, SlapperSilverfish, SlapperSkeleton, SlapperSkeletonHorse,
    SlapperSlime, SlapperSnowman, SlapperSpider, SlapperSquid,
    SlapperStray, SlapperVex, SlapperVillager, SlapperVindicator,
    SlapperWitch, SlapperWither, SlapperWitherSkeleton, SlapperWolf,
    SlapperZombie, SlapperZombieHorse, SlapperZombieVillager
};

use slapper\events\SlapperCreationEvent;
use slapper\events\SlapperDeletionEvent;
use slapper\events\SlapperHitEvent;


class Main extends PluginBase implements Listener {

    const ENTITY_TYPES = [
        "Chicken", "Pig", "Sheep", "Cow",
        "MushroomCow", "Wolf", "Enderman", "Spider",
        "Skeleton", "PigZombie", "Creeper", "Slime",
        "Silverfish", "Villager", "Zombie", "Human",
        "Bat", "CaveSpider", "LavaSlime", "Ghast",
        "Ocelot", "Blaze", "ZombieVillager", "Snowman",
        "Minecart", "FallingSand", "Boat", "PrimedTNT",
        "Horse", "Donkey", "Mule", "SkeletonHorse",
        "ZombieHorse", "Witch", "Rabbit", "Stray",
        "Husk", "WitherSkeleton", "IronGolem", "Snowman",
        "LavaSlime", "Squid", "ElderGuardian", "Endermite",
        "Evoker", "Guardian", "PolarBear", "Shulker",
        "Vex", "Vindicator", "Wither", "Llama"
    ];

    const ENTITY_ALIASES = [
		"MagmaCube" => "LavaSlime",
        "ZombiePigman" => "PigZombie",
        "Mooshroom" => "MushroomCow",
        "Player" => "Human",
        "VillagerZombie" => "ZombieVillager",
        "SnowGolem" => "Snowman",
        "FallingBlock" => "FallingSand",
        "FakeBlock" => "FallingSand",
        "VillagerGolem" => "IronGolem",
        "EGuardian" => "ElderGuardian",
        "Emite" => "Endermite"
    ];

    /** @var array */
    public $hitSessions = [];
    /** @var array */
    public $idSessions = [];
    /** @var string */
    public $prefix = TextFormat::GREEN . "[" . TextFormat::YELLOW . "Slapper" . TextFormat::GREEN . "] ";
    /** @var string */
    public $noperm = TextFormat::GREEN . "[" . TextFormat::YELLOW . "Slapper" . TextFormat::GREEN . "] Bạn không có quyền để làm điều này.";
    /** @var string */
    public $helpHeader =
        TextFormat::YELLOW . "---------- " .
        TextFormat::GREEN . "[" . TextFormat::YELLOW . "Slapper Trợ Giúp" . TextFormat::GREEN . "] " .
        TextFormat::YELLOW . "----------";

    /** @var string[] */
    public $mainArgs = [
        "▶ /slapper help: hiển thị tất cả lệnh.",
        "▶ /slapper spawn <loại slapper> [tên]: tạo một slapper.",
        "▶ /slapper edit [id] [args...]: chỉnh sửa slapper.",
        "▶ /slapper id: xem id của slapper.",
        "▶ /slapper remove [id]: xóa slapper.",
        "▶ /slapper version: xem phiên bản plugin.",
        "▶ /slapper cancel: hủy bỏ.",
    ];
    /** @var string[] */
    public $editArgs = [
        "▶ /slapper edit <id> helmet <id>: thêm/xóa mũ của slapper.",
        "▶ /slapper edit <id> chestplate <id>: thêm/xóa áo của slapper.",
        "▶ /slapper edit <id> leggings <id>: thêm/xóa quần của slapper.",
        "▶ /slapper edit <id> boots <id>: thêm/xóa giày của slapper.",
        "▶ /slapper edit <id> skin: tùy chỉnh skin của slapper.",
        "▶ /slapper edit <id> name <tên>: thay đổi tên slapper.",
        "▶ /slapper edit <id> addcommand <lệnh>: thêm lệnh vào slapper.",
        "▶ /slapper edit <id> delcommand <lệnh>: xóa lệnh của slapper.",
        "▶ /slapper edit <id> listcommands: xem tất cả lệnh của một slapper.",
        "▶ /slapper edit <id> block <id[:meta]>: tùy chỉnh slapper dạng block.",
        "▶ /slapper edit <id> scale <kích thước>: tăng/giảm kích thước slapper.",
        "▶ /slapper edit <id> tphere: dịch chuyển slapper đến người chơi.",
        "▶ /slapper edit <id> tpto: dịch chuyển người chơi đến slapper.",
        "▶ /slapper edit <id> menuname <tên/remove>: thay đổi tên hiển thị của slapper trên tab."
    ];

    /**
     * @return void
     */
    public function onEnable(): void {
        foreach ([
                     SlapperCreeper::class, SlapperBat::class, SlapperSheep::class,
                     SlapperPigZombie::class, SlapperGhast::class, SlapperBlaze::class,
                     SlapperIronGolem::class, SlapperSnowman::class, SlapperOcelot::class,
                     SlapperZombieVillager::class, SlapperHuman::class, SlapperCow::class,
                     SlapperZombie::class, SlapperSquid::class, SlapperVillager::class,
                     SlapperSpider::class, SlapperPig::class, SlapperMushroomCow::class,
                     SlapperWolf::class, SlapperLavaSlime::class, SlapperSilverfish::class,
                     SlapperSkeleton::class, SlapperSlime::class, SlapperChicken::class,
                     SlapperEnderman::class, SlapperCaveSpider::class, SlapperBoat::class,
                     SlapperMinecart::class, SlapperMule::class, SlapperWitch::class,
                     SlapperPrimedTNT::class, SlapperHorse::class, SlapperDonkey::class,
                     SlapperSkeletonHorse::class, SlapperZombieHorse::class, SlapperRabbit::class,
                     SlapperStray::class, SlapperHusk::class, SlapperWitherSkeleton::class,
                     SlapperFallingSand::class, SlapperElderGuardian::class, SlapperEndermite::class,
                     SlapperEvoker::class, SlapperGuardian::class, SlapperLlama::class,
                     SlapperPolarBear::class, SlapperShulker::class, SlapperVex::class,
                     SlapperVindicator::class, SlapperWither::class
                 ] as $className) {
            Entity::registerEntity($className, true);
        }
		$this->getLogger()->info("§aSlapper[việt hóa] v1.5.2 đã được bật!");
		$this->getLogger()->info("§aPlugin được dịch bởi Sói");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * @param CommandSender $sender
     * @param Command       $command
     * @param string        $label
     * @param string[]      $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        switch (strtolower($command->getName())) {
            case "nothing":
                return true;
            case "rca":
                if (count($args) < 2) {
                    $sender->sendMessage($this->prefix . "Hãy nhập tên người chơi và câu lệnh.");
                    return true;
                }
                $player = $this->getServer()->getPlayer(array_shift($args));
                if ($player instanceof Player) {
                    $this->getServer()->dispatchCommand($player, trim(implode(" ", $args)));
                    return true;
                } else {
                    $sender->sendMessage($this->prefix . "Không tìm thấy người chơi.");
                    return true;
                }
            case "slapper":
                if ($sender instanceof Player) {
                    if (!isset($args[0])) {
                        if (!$sender->hasPermission("slapper.command")) {
                            $sender->sendMessage($this->noperm);
                            return true;
                        } else {
                            $sender->sendMessage($this->prefix . "Hãy nhập '/slapper help'.");
                            return true;
                        }
                    }
                    $arg = array_shift($args);
                    switch ($arg) {
                        case "id":
                            if (!$sender->hasPermission("slapper.id")) {
                                $sender->sendMessage($this->noperm);
                                return true;
                            }
                            $this->idSessions[$sender->getName()] = true;
                            $sender->sendMessage($this->prefix . "Nhấn vào slapper để lấy id.");
                            return true;
                        case "version":
                            if (!$sender->hasPermission("slapper.version")) {
                                $sender->sendMessage($this->noperm);
                                return true;
                            }
                            $desc = $this->getDescription();
                            $sender->sendMessage($this->prefix . TextFormat::BLUE . $desc->getName() . " " . $desc->getVersion() . " " . TextFormat::GREEN . "bởi " . TextFormat::GOLD . "jojoe77777");
                            return true;
                        case "cancel":
                        case "stopremove":
                        case "stopid":
                            unset($this->hitSessions[$sender->getName()]);
                            unset($this->idSessions[$sender->getName()]);
                            $sender->sendMessage($this->prefix . "Đã hủy bỏ.");
                            return true;
                        case "remove":
                            if (!$sender->hasPermission("slapper.remove")) {
                                $sender->sendMessage($this->noperm);
                                return true;
                            }
                            if (!isset($args[0])) {
                                $this->hitSessions[$sender->getName()] = true;
                                $sender->sendMessage($this->prefix . "Nhấn vào slapper để xóa.");
                                return true;
                            }
                            $entity = $sender->getLevel()->getEntity((int) $args[0]);
                            if ($entity !== null) {
                                if ($entity instanceof SlapperEntity || $entity instanceof SlapperHuman) {
                                    $this->getServer()->getPluginManager()->callEvent(new SlapperDeletionEvent($entity));
                                    $entity->close();
                                    $sender->sendMessage($this->prefix . "Slapper đã được xóa.");
                                } else {
                                    $sender->sendMessage($this->prefix . "Đây không phải slapper.");
                                }
                            } else {
                                $sender->sendMessage($this->prefix . "Slapper không tồn tại.");
                            }
                            return true;
                        case "edit":
                            if (!$sender->hasPermission("slapper.edit")) {
                                $sender->sendMessage($this->noperm);
                                return true;
                            }
                            if (isset($args[0])) {
                                $level = $sender->getLevel();
                                $entity = $level->getEntity((int) $args[0]);
                                if ($entity !== null) {
                                    if ($entity instanceof SlapperEntity || $entity instanceof SlapperHuman) {
                                        if (isset($args[1])) {
                                            switch ($args[1]) {
                                                case "helm":
                                                case "helmet":
                                                case "head":
                                                case "hat":
                                                case "cap":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $entity->getArmorInventory()->setHelmet(Item::fromString($args[2]));
                                                            $sender->sendMessage($this->prefix . "Đã cập nhật mũ.");
                                                        } else {
                                                            $sender->sendMessage($this->prefix . "Hãy nhập id mũ.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Không thể đeo mũ cho slapper này.");
                                                    }
                                                    return true;
                                                case "chest":
                                                case "shirt":
                                                case "chestplate":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $entity->getArmorInventory()->setChestplate(Item::fromString($args[2]));
                                                            $sender->sendMessage($this->prefix . "Đã cập nhật áo.");
                                                        } else {
                                                            $sender->sendMessage($this->prefix . "Hãy nhập id áo.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Không thể mặc áo cho slapper này.");
                                                    }
                                                    return true;
                                                case "pants":
                                                case "legs":
                                                case "leggings":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $entity->getArmorInventory()->setLeggings(Item::fromString($args[2]));
                                                            $sender->sendMessage($this->prefix . "Đã cập nhật quần.");
                                                        } else {
                                                            $sender->sendMessage($this->prefix . "Hãy nhập id quần.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Không thể mặc quần cho slapper này.");
                                                    }
                                                    return true;
                                                case "feet":
                                                case "boots":
                                                case "shoes":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $entity->getArmorInventory()->setBoots(Item::fromString($args[2]));
                                                            $sender->sendMessage($this->prefix . "Đã cập nhật giày.");
                                                        } else {
                                                            $sender->sendMessage($this->prefix . "Hãy nhập id giày.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Không thể đeo giày cho slapper này.");
                                                    }
                                                    return true;
                                                case "hand":
                                                case "item":
                                                case "holding":
                                                case "arm":
                                                case "held":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $entity->getInventory()->setItemInHand(Item::fromString($args[2]));
                                                            $entity->getInventory()->sendHeldItem($entity->getViewers());
                                                            $sender->sendMessage($this->prefix . "Đã cập nhật vật phẩm được cầm trên tay slapper.");
                                                        } else {
                                                            $sender->sendMessage($this->prefix . "Hãy nhập id vật phẩm.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Slapper này không thể cầm item.");
                                                    }
                                                    return true;
                                                case "setskin":
                                                case "changeskin":
                                                case "editskin";
                                                case "skin":
                                                    if ($entity instanceof SlapperHuman) {
                                                        $entity->setSkin($sender->getSkin());
                                                        $entity->sendData($entity->getViewers());
                                                        $sender->sendMessage($this->prefix . "Đã cập nhật trang phục.");
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Không thể đặt trang phục cho slapper này.");
                                                    }
                                                    return true;
                                                case "name":
                                                case "customname":
                                                    if (isset($args[2])) {
                                                        array_shift($args);
                                                        array_shift($args);
                                                        $entity->setNameTag(str_replace(["{color}", "{line}"], ["ยง", "\n"], trim(implode(" ", $args))));
                                                        $entity->sendData($entity->getViewers());
                                                        $sender->sendMessage($this->prefix . "Đã cập nhật tên.");
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Hãy nhập một cái tên.");
                                                    }
                                                    return true;
                                                case "listname":
                                                case "nameonlist":
                                                case "menuname":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $type = 0;
                                                            array_shift($args);
                                                            array_shift($args);
                                                            $input = trim(implode(" ", $args));
                                                            switch (strtolower($input)) {
                                                                case "remove":
                                                                case "":
                                                                case "disable":
                                                                case "off":
                                                                case "hide":
                                                                    $type = 1;
                                                            }
                                                            if ($type === 0) {
                                                                $entity->namedtag->setString("MenuName", $input);
                                                            } else {
                                                                $entity->namedtag->setString("MenuName", "");
                                                            }
                                                            $entity->respawnToAll();
                                                            $sender->sendMessage($this->prefix . "Đã cập nhật tên hiển thị trên menu.");
                                                        } else {
                                                            $sender->sendMessage($this->prefix . "Hãy nhập một cái tên.");
                                                            return true;
                                                        }
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Không thể đặt tên hiển thị trên menu cho slapper này.");
                                                    }
                                                    return true;
                                                case "addc":
                                                case "addcmd":
                                                case "addcommand":
                                                    if (isset($args[2])) {
                                                        array_shift($args);
                                                        array_shift($args);
                                                        $input = trim(implode(" ", $args));

                                                        $commands = $entity->namedtag->getCompoundTag("Commands") ?? new CompoundTag("Commands");

                                                        if ($commands->hasTag($input)) {
                                                            $sender->sendMessage($this->prefix . "Câu lệnh này đã được thêm.");
                                                            return true;
                                                        }
                                                        $commands->setString($input, $input);
                                                        $entity->namedtag->setTag($commands); //in case a new CompoundTag was created
                                                        $sender->sendMessage($this->prefix . "Đã thêm lệnh.");
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Hãy nhập một lệnh.");
                                                    }
                                                    return true;
                                                case "delc":
                                                case "delcmd":
                                                case "delcommand":
                                                case "removecommand":
                                                    if (isset($args[2])) {
                                                        array_shift($args);
                                                        array_shift($args);
                                                        $input = trim(implode(" ", $args));

                                                        $commands = $entity->namedtag->getCompoundTag("Commands") ?? new CompoundTag("Commands");

                                                        $commands->removeTag($input);
                                                        $entity->namedtag->setTag($commands); //in case a new CompoundTag was created
                                                        $sender->sendMessage($this->prefix . "Đã xóa lệnh.");
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Hãy nhập lệnh.");
                                                    }
                                                    return true;
                                                case "listcommands":
                                                case "listcmds":
                                                case "listcs":
                                                    $commands = $entity->namedtag->getCompoundTag("Commands");
                                                    if ($commands !== null and $commands->getCount() > 0) {
                                                        $id = 0;

                                                        /** @var StringTag $stringTag */
                                                        foreach ($commands as $stringTag) {
                                                            $id++;
                                                            $sender->sendMessage(TextFormat::GREEN . "[" . TextFormat::YELLOW . "S" . TextFormat::GREEN . "] " . TextFormat::YELLOW . $id . ". " . TextFormat::GREEN . $stringTag->getValue() . "\n");
                                                        }
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Slapper này không có lệnh nào.");
                                                    }
                                                    return true;
                                                case "block":
                                                case "tile":
                                                case "blockid":
                                                case "tileid":
                                                    if (isset($args[2])) {
                                                        if ($entity instanceof SlapperFallingSand) {
                                                            $data = explode(":", $args[2]);
                                                            //haxx: we shouldn't use toStaticRuntimeId() because it's internal, but there isn't really any better option at the moment
                                                            $entity->getDataPropertyManager()->setInt(Entity::DATA_VARIANT, BlockFactory::toStaticRuntimeId((int) ($data[0] ?? 1), (int) ($data[1] ?? 0)));
                                                            $entity->sendData($entity->getViewers());
                                                            $sender->sendMessage($this->prefix . "Đã cập nhật block.");
                                                        } else {
                                                            $sender->sendMessage($this->prefix . "Đây không phải một block.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Hãy nhật id block.");
                                                    }
                                                    return true;
                                                case "teleporthere":
                                                case "tphere":
                                                case "movehere":
                                                case "bringhere":
                                                    $entity->teleport($sender);
                                                    $sender->sendMessage($this->prefix . "Đã dịch chuyển slapper tới bạn.");
                                                    $entity->respawnToAll();
                                                    return true;
                                                case "teleportto":
                                                case "tpto":
                                                case "goto":
                                                case "teleport":
                                                case "tp":
                                                    $sender->teleport($entity);
                                                    $sender->sendMessage($this->prefix . "Đã dịch chuyển bạn tới slapper.");
                                                    return true;
                                                case "scale":
                                                case "size":
                                                    if (isset($args[2])) {
                                                        $scale = (float) $args[2];
                                                        $entity->getDataPropertyManager()->setFloat(Entity::DATA_SCALE, $scale);
                                                        $entity->sendData($entity->getViewers());
                                                        $sender->sendMessage($this->prefix . "Đã cập nhật kích thước.");
                                                    } else {
                                                        $sender->sendMessage($this->prefix . "Hãy nhập một con số.");
                                                    }
                                                    return true;
                                                default:
                                                    $sender->sendMessage($this->prefix . "Lệnh không rõ.");
                                                    return true;
                                            }
                                        } else {
                                            $sender->sendMessage($this->helpHeader);
                                            foreach ($this->editArgs as $msgArg) {
                                                $sender->sendMessage(str_replace("<id>", $args[0], TextFormat::GREEN . " - " . $msgArg . "\n"));
                                            }
                                            return true;
                                        }
                                    } else {
                                        $sender->sendMessage($this->prefix . "Đây không phải slapper.");
                                    }
                                } else {
                                    $sender->sendMessage($this->prefix . "Slapper không tồn tại.");
                                }
                                return true;
                            } else {
                                $sender->sendMessage($this->helpHeader);
                                foreach ($this->editArgs as $msgArg) {
                                    $sender->sendMessage(TextFormat::GREEN . $msgArg . "\n");
                                }
                                return true;
                            }
                        case "help":
                        case "?":
                            $sender->sendMessage($this->helpHeader);
                            foreach ($this->mainArgs as $msgArg) {
                                $sender->sendMessage(TextFormat::GREEN . $msgArg . "\n");
                            }
                            return true;
                        case "add":
                        case "make":
                        case "create":
                        case "spawn":
                        case "apawn":
                        case "spanw":
                            if (!$sender->hasPermission("slapper.create")) {
                                $sender->sendMessage($this->noperm);
                                return true;
                            }
                            $type = array_shift($args);
                            $name = str_replace(["{color}", "{line}"], ["ยง", "\n"], trim(implode(" ", $args)));
                            if ($type === null || empty(trim($type))) {
                                $sender->sendMessage($this->prefix . "Hãy nhập loại slapper.");
                                return true;
                            }
                            if (empty($name)) {
                                $name = $sender->getDisplayName();
                            }
                            $types = self::ENTITY_TYPES;
                            $aliases = self::ENTITY_ALIASES;
                            $chosenType = null;
                            foreach ($types as $t) {
                                if (strtolower($type) === strtolower($t)) {
                                    $chosenType = $t;
                                }
                            }
                            if ($chosenType === null) {
                                foreach ($aliases as $alias => $t) {
                                    if (strtolower($type) === strtolower($alias)) {
                                        $chosenType = $t;
                                    }
                                }
                            }
                            if ($chosenType === null) {
                                $sender->sendMessage($this->prefix . "Không tìm thấy loại slapper này.");
                                return true;
                            }
                            $nbt = $this->makeNBT($chosenType, $sender, $name);
                            /** @var SlapperEntity $entity */
                            $entity = Entity::createEntity("Slapper" . $chosenType, $sender->getLevel(), $nbt);
                            $this->getServer()->getPluginManager()->callEvent(new SlapperCreationEvent($entity, "Slapper" . $chosenType, $sender, SlapperCreationEvent::CAUSE_COMMAND));
                            $entity->spawnToAll();
							$sender->sendMessage($this->prefix . "§aĐã tạo thành công slapper");
                            $sender->sendMessage($this->prefix . "§aLoại slapper:§e $chosenType");
							$sender->sendMessage($this->prefix . "§aTên hiển thị:§e $name");
							$sender->sendMessage($this->prefix . "§aID được tạo: §e" . $entity->getId());
                            return true;
                        default:
                            $sender->sendMessage($this->prefix . "Lệnh không xác định. Nhập '/slapper help' để xem lệnh.");
                            return true;
                    }
                } else {
                    $sender->sendMessage($this->prefix . "Lệnh chỉ hoạt động trong game. Hổng phải CONSOLE :)");
                    return true;
                }
        }
        return true;
    }

    /**
     * @param string $type
     * @param Player $player
     * @param string $name
     *
     * @return CompoundTag
     */
    private function makeNBT($type, Player $player, string $name): CompoundTag {
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        $nbt->setShort("Health", 1);
        $nbt->setTag(new CompoundTag("Commands", []));
        $nbt->setString("MenuName", "");
        $nbt->setString("CustomName", $name);
        $nbt->setString("SlapperVersion", $this->getDescription()->getVersion());
        if ($type === "Human") {
            $player->saveNBT();

            $inventoryTag = $player->namedtag->getListTag("Inventory");
            assert($inventoryTag !== null);
            $nbt->setTag(clone $inventoryTag);

            $skinTag = $player->namedtag->getCompoundTag("Skin");
            assert($skinTag !== null);
            $nbt->setTag(clone $skinTag);
        }
        return $nbt;
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @ignoreCancelled true
     *
     * @return void
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof SlapperEntity || $entity instanceof SlapperHuman) {
            $event->setCancelled(true);
            if (!$event instanceof EntityDamageByEntityEvent) {
                return;
            }
            $damager = $event->getDamager();
            if (!$damager instanceof Player) {
                return;
            }
            $this->getServer()->getPluginManager()->callEvent($event = new SlapperHitEvent($entity, $damager));
            if ($event->isCancelled()) {
                return;
            }
            $damagerName = $damager->getName();
            if (isset($this->hitSessions[$damagerName])) {
                if ($entity instanceof SlapperHuman) {
                    $entity->getInventory()->clearAll();
                }
                $entity->close();
                unset($this->hitSessions[$damagerName]);
                $damager->sendMessage($this->prefix . "Đã xóa slapper.");
                return;
            }
            if (isset($this->idSessions[$damagerName])) {
                $damager->sendMessage($this->prefix . "ID của slapper: " . $entity->getId());
                unset($this->idSessions[$damagerName]);
                return;
            }

            if (($commands = $entity->namedtag->getCompoundTag("Commands")) !== null) {
                $server = $this->getServer();
                /** @var StringTag $stringTag */
                foreach ($commands as $stringTag) {
                    $server->dispatchCommand(new ConsoleCommandSender(), str_replace("{player}", '"' . $damagerName . '"', $stringTag->getValue()));
                }
            }
        }
    }

    /**
     * @param EntitySpawnEvent $ev
     *
     * @return void
     */
    public function onEntitySpawn(EntitySpawnEvent $ev): void {
        $entity = $ev->getEntity();
        if ($entity instanceof SlapperEntity || $entity instanceof SlapperHuman) {
            $clearLagg = $this->getServer()->getPluginManager()->getPlugin("ClearLagg");
            if ($clearLagg !== null) {
                $clearLagg->exemptEntity($entity);
            }
        }
    }

    /**
     * @param EntityMotionEvent $event
     *
     * @return void
     */
    public function onEntityMotion(EntityMotionEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof SlapperEntity || $entity instanceof SlapperHuman) {
            $event->setCancelled(true);
        }
    }
}
