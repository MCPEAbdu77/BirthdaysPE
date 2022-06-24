<?php

declare(strict_types=1);

namespace MCA7\BirthdaysPE;

use DateTime;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;


class BirthdaysPE extends PluginBase implements Listener
{
        private $db;
	private $HBD = []; //TODO
	private const SEP = " §l§8|§r ";

	public function onEnable(): void
	{
		$this->db = new Config($this->getDataFolder() . "birthdaylist.yml");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(): void
	{
		if (!$this->HBD) return;
		foreach ($this->HBD as $one) {
			unset($one);
		}
	}

	public function onJoin(PlayerJoinEvent $e): void
	{
		$prefix = $this->getConfig()->get('prefix');
		$player = $e->getPlayer();
		$date = date("m-d");
		$con = $this->db->getAll();
		$bdayboi = array_search($date, $con);
		if (array_search($date, $con)) {
			if ($bdayboi === $player->getName()) goto ToTheBirthdayBoi;
			$conmsg = $this->getConfig()->get('birthday-announcing-msg');
			$msg = str_replace(["{player}", "{birthdayboi}", "{line}"], [$player->getName(), $bdayboi, "\n"], $conmsg);
			$player->sendMessage($prefix . self::SEP . $msg);
			return;
		}
		return;
		ToTheBirthdayBoi:
		$conmsg = $this->getConfig()->get('birthday-msg-to-player');
		$msg = str_replace(["{player}", "{birthdayboi}", "{line}"], [$player->getName(), $bdayboi, "\n"], $conmsg);
		$player->sendMessage($prefix . self::SEP . $msg);
		$player->sendTitle(C::BOLD."Happy Birthday ^-^", $bdayboi, -1, 40, -1);
	}

	private function validateDate($date, $format = 'm-d')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) === $date;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
	{
		$prefix = $this->getConfig()->get('prefix');
		$player = $sender->getName();
		if ($command->getName() === "birthday") {
			if (!$sender instanceof Player) {
				$sender->sendMessage(C::RED . "Please execute in-game!");
				return true;
			}
			if (isset($args[0])) {
				switch ($args[0]) {
					case 'set':
						if (isset($args[1])) {
							if (!$this->db->getNested($player)) {
								$set = $args[1];
								if ($this->validateDate($set)) {
									$this->db->setNested($player, (string)$set);
									$sender->sendMessage($prefix . self::SEP . C::GREEN . "Birthday date has been set!");
									$this->db->save();
								} else {
									$sender->sendMessage($prefix . self::SEP . C::RED . "Invalid date! Please set a valid date. FORMAT: MM-DD");
								}
							} else {
								$sender->sendMessage($prefix . self::SEP . C::RED . "Your birthday date has been already set before! \n Use /birthday <reset> to reset your last set date.");
							}
						} else {
							$sender->sendMessage($prefix . self::SEP . C::RED . "Usecase: /birthday set <MONTH-DAY> \n Example: /birthday set 12-22 " . C::WHITE . "(which is December 22nd)");
						}
						return true;

					case 'reset':

						if ($this->db->getNested($player)) {
							$this->db->removeNested($player);
							$sender->sendMessage($prefix . self::SEP . C::GREEN . "Birthday date has been reset!");
							$this->db->save();
						} else {
							$sender->sendMessage($prefix . self::SEP . C::RED . "You've not set your Birthday before!");
						}
						return true;
				}
			} else {
				$sender->sendMessage($prefix . self::SEP . C::RED . "Usage: /birthday <set/reset>");
			}
		}
		return true;
	}
}
