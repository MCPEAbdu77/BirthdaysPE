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
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use SQLite3;


class BirthdaysPE extends PluginBase implements Listener
{

	private const SEP = " §l§8|§r ";
	private DataConnector $database;
	private $arrayed = [];


	public function onEnable(): void
	{
		$this->database = $database = libasynql::create($this, $this->getConfig()->get("database"), ["mysql" => "mysql.sql", "sqlite" => "sqlite.sql"]);
		$this->database->executeGeneric("init.load");
		$this->database->waitAll();
		$this->database->executeSelect("init.view", [], function(array $rows) : void {
            foreach($rows as $row) {
                $this->arrayed[] = $row["username"];
			}
		});
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}


	public function onDisable(): void
	{
		$this->database->waitAll();
		$this->database->close();
	}


	public function onJoin(PlayerJoinEvent $e): void
	{
		$prefix = $this->getConfig()->get('prefix');
		$player = $e->getPlayer();
		$date = date("m-d");
		if (in_array($player->getName(), $this->arrayed)) {
			$this->database->executeSelect("init.view", [], function(array $rows) use($player, $date, $prefix) : void {
				foreach($rows as $row) {
					if($row["username"] === $player->getName() && $row["date"] === $date) {
						$conmsg = $this->getConfig()->get('birthday-msg-to-player');
						$msg = str_replace(["{player}", "{birthdayboi}", "{line}"], [$player->getName(), $row["username"], "\n"], $conmsg);
						$player->sendMessage($prefix . self::SEP . $msg);
						$player->sendTitle(C::BOLD."Happy Birthday ^-^", $row["username"], -1, 40, -1);
					} else {
                                                if($row["date"] === $date) {
							$conmsg = $this->getConfig()->get('birthday-announcing-msg');
							$msg = str_replace(["{player}", "{birthdayboi}", "{line}"], [$player->getName(), $row["username"], "\n"], $conmsg);
							$player->sendMessage($prefix . self::SEP . $msg);
						}
					}
				}
			});
		} else {
			$this->database->executeInsert("init.create", [
				"username" => $e->getPlayer()->getName(),
				"date" => ""
			]);
			$this->arrayed[] = $e->getPlayer()->getName();
		}
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
							$name = $sender->getName();
							$this->database->executeSelect("init.view", [], function(array $rows) use($name, $sender, $prefix, $args) : void {
								foreach($rows as $row) {
									if($row["username"] === $name) {
										if($row["date"] === "") {
											$set = $args[1];
											$set = (string)$set;
											if ($this->validateDate($set)) {
												$this->database->executeChange("init.update", ["username" => $sender->getName(), "date" => $set]);
												$sender->sendMessage($prefix . self::SEP . C::GREEN . "Birthday date has been set!");
											} else {
												$sender->sendMessage($prefix . self::SEP . C::RED . "Invalid date! Please set a valid date. FORMAT: MM-DD");
											}
										} else {
											$sender->sendMessage($prefix . self::SEP . C::RED . "Your birthday date has been already set before! \n Use /birthday <reset> to reset your last set date.");
										}
									}
								}
							});
						} else {
							$sender->sendMessage($prefix . self::SEP . C::RED . "Usecase: /birthday set <MONTH-DAY> \n Example: /birthday set 12-22 " . C::WHITE . "(which is December 22nd)");
						}
					return true;

					case 'reset':
                                           $this->database->executeSelect("init.view", [], function(array $rows) use($sender, $prefix) : void {
                                               foreach($rows as $row) {
                                                   if($row["username"] === $sender->getName()) {
                                                       if($row["date"] !== "") {
                                                           $this->database->executeChange("init.reset", ["username" => $sender->getName()]);
						           $sender->sendMessage($prefix . self::SEP . C::GREEN . "Birthday date has been reset!");
                                                       } else {
                                                           $sender->sendMessage($prefix . self::SEP . C::RED . "You've not set your Birthday before!");
                                                       }
                                                   }
	                  		      }
		                         });
				    return true;
				}
			} else {
				$sender->sendMessage($prefix . self::SEP . C::RED . "Usage: /birthday <set/reset>");
			}
		}
		
		return true;
	}

}
