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
	 * @param string $identifier use entity unique id if type is player or entity
	 */
	public function __construct(Scoreboard $scoreboard, int $line, int $score, int $type, string $identifier) {
		$this->scoreboardId = $scoreboard->getScoreboardId() + $line;
		$this->type = $type;
		if($type === self::TYPE_FAKE_PLAYER) {
			$this->customName = $identifier;
		}else {
			$this->entityUniqueId = $identifier;
		}
		$this->score = $score;
	}
}