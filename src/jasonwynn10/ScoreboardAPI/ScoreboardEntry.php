<?php
declare(strict_types=1);
namespace jasonwynn10\ScoreboardAPI;

use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;

class ScoreboardEntry extends ScorePacketEntry {
	/**
	 * ScoreboardEntry constructor.
	 *
	 * @param Scoreboard $scoreboard
	 * @param int $line
	 * @param int $score
	 * @param int $type
	 * @param int|string $identifier use entity unique id if type is player or entity
	 */
	public function __construct(Scoreboard $scoreboard, int $line, int $score, int $type, $identifier) {
		$this->objectiveName = $scoreboard->getObjectiveName();
		$this->scoreboardId = $scoreboard->getScoreboardId() + $line;
		$this->type = $type;
		if($type === self::TYPE_FAKE_PLAYER) {
			$this->customName = $identifier;
		}else {
			$this->entityUniqueId = $identifier;
		}
		$this->score = $score;
	}

	/**
	 * Automatically pads custom text according to score digit count
	 *
	 * @throws \Exception
	 */
	public function pad() : void {
		if($this->type !== self::TYPE_FAKE_PLAYER) {
			throw new \Exception("Entry type must be Fake Player in order to pad"); // throw exception rather than let devs wonder why it's not working
		}
		$scoreboard = ScoreboardAPI::getInstance()->getScoreboard($this->objectiveName);
		$scoreboard->removeEntry($this);
		$maxSpaces = 1;
		foreach($scoreboard->getEntries() as $entry) {
			$digitCount = strlen((string)$entry->score);
			if($maxSpaces < $digitCount) {
				$maxSpaces = $digitCount;
			}
		}
		if($this->customName{(strlen($this->customName)-1)} !== " ") {
			$this->customName = str_pad($this->customName, $maxSpaces - strlen((string)$this->score));
		}
		$scoreboard->addEntry($this);
	}
}