<?php

/**
 * содержит массив возможных фигур, и возвращает одну из них в виде объекта
 */
class Arm
{
	public const STONE = 1;
	public const SCISSORS = 2;
	public const PAPER = 3;

	/**
	 * возвращает массив со всеми возможными "фигурами" руки
	 * @return array $items
	 */
	private static function items()
	{
		$items = [
			[
				'id' => self::STONE,
				'title' => 'камень'
			],
			[
				'id' => self::SCISSORS,
				'title' => 'бумага'
			],
			[
				'id' => self::PAPER,
				'title' => 'ножницы'
			],
		];
		return $items;
	}

	/**
	 * возвращает id случайной фигуры
	 * @return int id of random $item
	 */
	public static function getItemId()
	{
		$items = self::items();
		$randomKey = array_rand($items);
		return $items[$randomKey]['id'];
	}

	/**
	 * возвращает название фигуры
	 * @param int $id идентификатор фигуры
	 * @return string название фигуры
	 */
	public static function getTitle($id)
	{
		foreach (self::items() as $item) {
			if ($item['id'] == $id) return $item['title'];
		}
	}
}


/**
 * создание игроков, получение их данных, редактирование
 */
class Player
{
	/**
	 * создание игрока
	 * @param int $id указать id игрока, должен быть уникальным
	 * @param string $name имя игрока, не обязательный параметр
	 * @param int $coins количество игровых монет у игрока, по умолчанию 100
	 * @return object player возвращает игрока в виде объекта 
	 */
	function create($id, $name = null, $coins = 100)
	{
		$this->id = $id; // id игрока
		$this->name = $name; // имя игрока
		$this->totalGame = 0; // всего сыгранных игр
		$this->win = 0; // всего побед
		$this->coins = $coins; // монет на счету
		$this->maxCoins = $coins; // максимум монет
		$this->arm = null; // id выброшенной фигуры
		return $this;
	}

	/**
	 * получить id игрока
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return object возвращает информацию о игроке в виде объекта
	 */
	public function show()
	{
		return $this;
	}

	/**
	 * определяет какую фигуру покажет игрок
	 */
	public function setArm()
	{
		return $this->arm = Arm::getItemId();
	}

	/**
	 * вывести фигуру
	 */
	public function getArm()
	{
		return $this->arm;
	}

	/**
	 * освободить руку
	 */
	public function freeArm()
	{
		$this->arm = null;
	}

	/**
	 * добавляет монеты игроку
	 * @param int $coins количество монет, добавляемых игроку
	 */
	public function addCoins($coins)
	{
		$this->coins = $this->coins + $coins;
	}

	/**
	 * забирает монеты у игрока
	 * @param int $coins количество монет, забираемых у игрока
	 */
	public function removeCoins($coins)
	{
		$this->coins = $this->coins - $coins;
	}

	/**
	 * возвращает количество монет у игрока
	 * @return int coins
	 */
	public function getCoins()
	{
		return $this->coins;
	}

	public function setMaxCoins()
	{
		if ($this->maxCoins < $this->coins) {
			$this->maxCoins = $this->coins;
		}
	}

	/**
	 * увеличить счетчик побед у игрока на 1
	 */
	public function addWin()
	{
		++$this->win;
	}

	/**
	 * общее количество сыгранных игр равно количеству сыгранных раундов
	 * @param int $gameCount
	 */
	public function addTotalGame($gameCount)
	{
		$this->totalGame = $gameCount;
	}
}

class Game
{
	private $roundAmount; // количество раундов, которые будут сыграны
	private $playedRound = 0; // сыгранные раунды
	private $bet; // размер ставки в игре
	private $players = []; // массив с пользователями
	private $countPlayers = 0;
	private $gameResult = []; // результаты игры
	private $drawTax = 0; // штраф за ничью
	private $drawRate = 0; // количество ничьих до штрафа
	private $drawCount = 0; // временный счетчик ничьих
	private $drawTotalCount = 0; // общая статистика по ничьим
	private $itemsStatistic = []; // статистика по показанным фигурам

	/**
	 * добавить игрока в игру
	 */
	public function goToGame(Player $player)
	{
		$this->playersCheckId($player);
		$this->players[] = $player;
		return $this;
	}

	/**
	 * добавить несколько игроков в игру 
	 */
	public function setPlayers($players = [])
	{
		if (!is_array($players)) die('Переданный параметр в функцию setPlayers должен быть массивом!');

		foreach ($players as $player) {
			$this->playersCheckId($player);
			$this->players[] = $player;
		}
		return $this;
	}

	/**
	 * вывести игроков в виде массива
	 */
	public function getPlayers()
	{
		return $this->players;
	}

	/**
	 * показать количество игроков
	 */
	private function countPlayers()
	{
		return $this->countPlayers = count($this->players);
	}

	/**
	 * проверка уникальности id у игроков
	 */
	private function playersCheckId(Player $player)
	{
		foreach ($this->players as $gamer) {
			if ($player->getId() == $gamer->getId()) {
				var_dump('У нового игрока: ', $player->show(), 'Id совпадает с уже существующим: ', $gamer->show());
				die('Id у игрока должен быть уникальным! ');
			}
		}
	}

	/**
	 * проверяет количество монет на счету у игроков
	 * если меньше игровой ставки, то игрок удаляется из игры и заносится в турнирную таблицу
	 */
	private function playersCoinsCheck()
	{
		for ($i = 0; $i < $this->countPlayers(); $i++) {
			if ($this->players[$i]->getCoins() < $this->bet) {

				$this->gameResult[$this->countPlayers()] = $this->players[$i];
				unset($this->players[$i]);
			}
		}
	}

	/**
	 * сортирует игроков по количеству монет,
	 * применяется, если к концу игры у игроков еще остались монеты на счету;
	 * такое может произойти, если указать количество раундов 
	 */
	private function sortPlayersByCoins()
	{
		foreach ($this->players as $player) {
			$array[$player->getId()] = $player->getCoins();
		}

		arsort($array);
		$i = 1;

		foreach ($array as $id => $coins) {
			foreach ($this->players as $player) {
				if ($player->getId() == $id) {
					$this->gameResult[$i++] = $player;
				}
			}
		}
	}

	/**
	 * добавляет последнего оставшегося игрока в турнирную таблицу
	 */
	private function setGameWinner()
	{
		$key = array_key_first($this->players);
		$this->gameResult[1] = $this->players[$key];
	}

	/**
	 * выводит результаты игры в виде массива
	 * игрок с ключом 1 - победитель;
	 * первый выбывший из игры под самым последним номером/ключом
	 */
	public function gameResult()
	{
		ksort($this->gameResult);
		return $this->gameResult;
	}

	/**
	 * Установить количество раундов,
	 * если не указать значение,
	 * то игра будет длится, пока один из игроков не выиграет все монеты противников
	 */
	public function setRoundAmount($amount = null)
	{
		$this->roundAmount = $amount;
		return $this;
	}

	/**
	 * вывести количество раундов, указанное пользователем
	 */
	public function getRoundAmount()
	{
		return $this->roundAmount;
	}

	/**
	 * счетчик сыгранных раундов
	 */
	private function playedRoundCount()
	{
		++$this->playedRound;
	}

	/**
	 * показать количество сыгранных раундов
	 */
	public function showPlayedRound()
	{
		return $this->playedRound;
	}

	/**
	 * устанавливает количество монет для ставки на каждый раунд игры, 
	 * она будет добавлена к монетам победителя
	 * и забрана у проигравшего
	 * Если победителей несколько, то ставка делится поровну между ними
	 */
	public function setBet($bet = 1)
	{
		$this->bet = $bet;
		return $this;
	}

	/**
	 * вывести текущую ставку
	 */
	public function getBet()
	{
		return $this->bet;
	}

	/**
	 * Старт игры.
	 * проверяет все введенные данные, количество игроков и,
	 * если нет ошибок, то стартует игру,
	 * иначе выводит ошибки
	 */
	public function start()
	{
		// проверка количества игроков
		if ($this->countPlayers() < 2) {
			echo "Игроков должно быть 2 или 3! <br/>
			сейчас в игре: " . $this->countPlayers();
			die;
		}
		if ($this->countPlayers() > 3) {
			echo "Игроков должно быть 2 или 3! <br/>
			сейчас в игре: " . $this->countPlayers() . "<br />
			этот функционал в разработке";
			die;
		}

		//проверка размера ставки, штрафа за ничью и количества монет на счету у игроков
		foreach ($this->players as $player) {
			if ($player->getCoins() < $this->bet) {
				var_dump('У игрока: ', $player->show(), 'не хватает монет для начала игры!' . PHP_EOL . 'Размер ставки: ' . $this->bet . PHP_EOL . 'У игрока на счету: ' . $player->getCoins());
				die;
			}
			if ($player->getCoins() < $this->drawTax) {
				var_dump('У игрока: ', $player->show(), 'не хватает монет для начала игры!' . PHP_EOL . 'Размер штрафа за ничью: ' . $this->drawTax . PHP_EOL . 'У игрока на счету: ' . $player->getCoins());
				die;
			}
		}

		// если ошибок нет, начинаем игру!
		$this->gameProcess();

		// вывод результатов
		$this->gameResult();
	}

	/**
	 * игровой процесс
	 * определяет количество оставшихся игроков, количество монет на их счету
	 * повторяет указанное число раундов
	 * или пока у противников не закончатся монеты
	 */
	private function gameProcess()
	{
		// если количество раундов не указано, то игра идет, пока у противника хватает монет для ставки.
		if (is_null($this->getRoundAmount())) {
			while (true) {
				$this->playersCoinsCheck();

				if ($this->countPlayers() < 2) {
					$this->setGameWinner();
					break;
				}

				$this->playedRoundCount();
				$this->roundProcess();
			}
		}

		// если количество раундов задано, то игра идет указанное число раундов, или пока у противника не закончатся монеты
		if (is_numeric($this->getRoundAmount())) {
			for ($i = 0; $i < $this->getRoundAmount(); $i++) {
				$this->playersCoinsCheck();

				if ($this->countPlayers() < 2) {
					$this->setGameWinner();
					break;
				}

				$this->playedRoundCount();
				$this->roundProcess();
			}

			$this->sortPlayersByCoins();
		}
	}

	/**
	 * игровой раунд
	 * выбирает тип проведения игры, зависит от количества игроков
	 */
	private function roundProcess()
	{
		$this->players = array_values($this->players);
		// игроки выбирают фигуру а так же подсчет выбранных фигур
		foreach ($this->players as $player) {
			$this->countItem($player->setArm());
		}

		// сравнение фигур
		if ($this->countPlayers() == 2) $this->twoArmCheck($this->players[0], $this->players[1]);
		if ($this->countPlayers() > 2) $this->threeArmCheck($this->players[0], $this->players[1], $this->players[2]);
	}

	/**
	 * Сравнение рук у двух игроков
	 */
	private function twoArmCheck($player_1, $player_2)
	{
		/* var_dump($player_1->getArm(), $player_2->getArm());
		
		var_dump($player_1->getArm() == Arm::STONE);
		var_dump($player_1->getArm() == Arm::SCISSORS);
		var_dump($player_1->getArm() == Arm::PAPER); */

		// если оба игрока выбросили одинаковую фигуру, то ничья
		if ($player_1->getArm() == $player_2->getArm()) {
			$this->draw([$player_1, $player_2]);
		}

		// сравнение фигур
		switch ($player_1->getArm()) {
				// у первого игрока - камень
			case Arm::STONE:
				// камень выигрывает у ножниц
				if ($player_2->getArm() == Arm::SCISSORS) {
					$this->winner([$player_1]);
					$this->loser([$player_2]);
				}
				// камень проигрывает бумаге
				if ($player_2->getArm() == Arm::PAPER) {
					$this->winner([$player_2]);
					$this->loser([$player_1]);
				}
				break;

				// у первого игрока - ножницы
			case Arm::SCISSORS:
				// ножницы выигрывают у бумаги
				if ($player_2->getArm() == Arm::PAPER) {
					$this->winner([$player_1]);
					$this->loser([$player_2]);
				}
				// ножницы проигрывают камню
				if ($player_2->getArm() == Arm::STONE) {
					$this->winner([$player_2]);
					$this->loser([$player_1]);
				}
				break;

				// у первого игрока - бумага
			case Arm::PAPER:
				// бумага выигрывает у камня
				if ($player_2->getArm() == Arm::STONE) {
					$this->winner([$player_1]);
					$this->loser([$player_2]);
				}
				// бумага проигрывает ножницам
				if ($player_2->getArm() == Arm::SCISSORS) {
					$this->winner([$player_2]);
					$this->loser([$player_1]);
				}
				break;

			default:
				break;
		}
		$player_1->freeArm();
		$player_2->freeArm();
	}

	/**
	 * Сравнение рук у трех игроков
	 */
	private function threeArmCheck($player_1, $player_2, $player_3)
	{

		/* var_dump($player_1->getArm(), $player_2->getArm());
		
		var_dump($player_1->getArm() == Arm::STONE);
		var_dump($player_1->getArm() == Arm::SCISSORS);
		var_dump($player_1->getArm() == Arm::PAPER); */

		// если все игроки выбросили одинаковую фигуру, то ничья
		if ($player_1->getArm() == $player_2->getArm() and $player_1->getArm() == $player_3->getArm()) {
			$this->draw([$player_1, $player_2, $player_3]);
		}

		// если все игроки выбросили разные фигуры, то ничья
		if ($player_1->getArm() != $player_2->getArm() and $player_1->getArm() != $player_3->getArm() and $player_2->getArm() != $player_3->getArm()) {
			$this->draw([$player_1, $player_2, $player_3]);
		}


		// сравнение фигур у первых двух игроков
		if ($player_1->getArm() == $player_2->getArm()) {
			$gamers = [$player_1, $player_2];
		} else {
			switch ($player_1->getArm()) {
					// у первого игрока - камень
				case Arm::STONE:
					// камень выигрывает у ножниц
					if ($player_2->getArm() == Arm::SCISSORS) {
						$gamers[] = $player_1;
						$this->loser([$player_2]);
					}
					// камень проигрывает бумаге
					if ($player_2->getArm() == Arm::PAPER) {
						$gamers[] = $player_2;
						$this->loser([$player_1]);
					}
					break;

					// у первого игрока - ножницы
				case Arm::SCISSORS:
					// ножницы выигрывают у бумаги
					if ($player_2->getArm() == Arm::PAPER) {
						$gamers[] = $player_1;
						$this->loser([$player_2]);
					}
					// ножницы проигрывают камню
					if ($player_2->getArm() == Arm::STONE) {
						$gamers[] = $player_2;
						$this->loser([$player_1]);
					}
					break;

					// у первого игрока - бумага
				case Arm::PAPER:
					// бумага выигрывает у камня
					if ($player_2->getArm() == Arm::STONE) {
						$gamers[] = $player_1;
						$this->loser([$player_2]);
					}
					// бумага проигрывает ножницам
					if ($player_2->getArm() == Arm::SCISSORS) {
						$gamers[] = $player_2;
						$this->loser([$player_1]);
					}
					break;

				default:
					break;
			}
		}

		// сравнение фигур у победителя и третьего игрока
		switch ($gamers[0]->getArm()) {
				// у геймера - камень
			case Arm::STONE:
				// камень выигрывает у ножниц
				if ($player_3->getArm() == Arm::SCISSORS) {
					$this->winner($gamers);
					$this->loser([$player_3]);
				}
				// камень проигрывает бумаге
				if ($player_3->getArm() == Arm::PAPER) {
					$this->winner([$player_3]);
					$this->loser($gamers);
				}
				break;

				// у геймера - ножницы
			case Arm::SCISSORS:
				// ножницы выигрывают у бумаги
				if ($player_3->getArm() == Arm::PAPER) {
					$this->winner($gamers);
					$this->loser([$player_3]);
				}
				// ножницы проигрывают камню
				if ($player_3->getArm() == Arm::STONE) {
					$this->winner([$player_3]);
					$this->loser($gamers);
				}
				break;

				// у геймера - бумага
			case Arm::PAPER:
				// бумага выигрывает у камня
				if ($player_3->getArm() == Arm::STONE) {
					$this->winner($gamers);
					$this->loser([$player_3]);
				}
				// бумага проигрывает ножницам
				if ($player_3->getArm() == Arm::SCISSORS) {
					$this->winner([$player_3]);
					$this->loser($gamers);
				}
				break;

			default:
				break;
		}

		$player_1->freeArm();
		$player_2->freeArm();
		$player_3->freeArm();
	}

	/**
	 * победители
	 * @param array $winners  
	 * 
	 */
	private function winner($winners)
	{
		$currentBet = $this->bet / count($winners);
		foreach ($winners as $winner) {

			if (is_array($winner)) {
				var_dump($winners, $winner, count($winner));
				foreach ($winner as $gamer) {
					$gamer->addTotalGame($this->showPlayedRound());
					$gamer->addWin();
					$gamer->addCoins($currentBet);
					$gamer->setMaxCoins();
				}
				return;
			}

			$winner->addTotalGame($this->showPlayedRound());
			$winner->addWin();
			$winner->addCoins($currentBet);
			$winner->setMaxCoins();
		}
	}

	/**
	 * проигравшие
	 * @param array $losers 
	 */
	private function loser($losers)
	{
		foreach ($losers as $loser) {
			if (is_array($loser)) {
				foreach ($loser as $gamer) {
					$gamer->addTotalGame($this->showPlayedRound());
					$gamer->removeCoins($this->bet);
				}
				return;
			}
			$loser->addTotalGame($this->showPlayedRound());
			$loser->removeCoins($this->bet);
		}
	}

	/**
	 * ничья
	 * @param array $players
	 */
	private function draw($players)
	{
		++$this->drawTotalCount;
		foreach ($players as $player) {
			if (is_array($player)) {
				foreach ($player as $gamer) {
					$gamer->addTotalGame($this->showPlayedRound());
					$this->makeDrawTax($gamer);
				}
				$this->drawCountClear();
				return;
			}
			$player->addTotalGame($this->showPlayedRound());
			$this->makeDrawTax($player);
		}
		$this->drawCountClear();
		++$this->drawCount;
	}

	/**
	 * установить штраф за ничью,
	 * т.е кол-во монет, которые будут сниматься со счета игроков за ничью;
	 * а так же количество игр сыгранных в ничью,
	 * после которого будет начислен штраф
	 * @param int $tax кол-во монет, если 0, то без штрафа
	 * @param int $rate кол-во игр подряд, если 0, то за каждую ничью
	 */
	public function setDrawTax($tax = 0, $rate = 0)
	{
		if (!is_numeric($tax)) die('Параметр tax в методе setDrawTax должен быть числом!!');
		if (!is_numeric($rate)) die('Параметр rate в методе setDrawTax должен быть числом!!');
		$this->drawTax = $tax;
		$this->drawRate = $rate;
		return $this;
	}

	/**
	 * вывести значение штрафа за ничью
	 */
	public function getDrawTax()
	{
		return $this->drawTax;
	}

	/**
	 * забирает штраф с игрока за ничью
	 * @param object $gamer
	 */
	private function makeDrawTax($gamer)
	{
		if ($this->drawTax > 0) {
			if ($this->drawRate == $this->drawCount) {
				$gamer->removeCoins($this->drawTax);
			}
		}
	}

	/**
	 * очищает счетчик ничьих
	 */
	private function drawCountClear()
	{
		if ($this->drawRate == $this->drawCount) {
			$this->drawCount = 0;
		}
	}

	/**
	 * вывести количество ничьих за игру
	 */
	public function drawStatistic()
	{
		return $this->drawTotalCount;
	}

	/**
	 * подсчет выброшенных фигур
	 */
	private function countItem($itemId)
	{
		++$this->itemsStatistic[$itemId];
	}

	/**
	 * показать статистику по показанным фигурам
	 */
	public function itemsStatistic()
	{
		arsort($this->itemsStatistic);
		return $this->itemsStatistic;
	}
}


/**
 * правила игры и возможности
 */

$coins = 500; // количество монет у игроков, так же можно для каждого игрока указать свое значение.

// создание игроков
$joe = new Player;
$joan = new Player;
$bob = new Player;

$joe->create(1, 'Joe', $coins);
$joan->create(2, 'Joan', $coins);
$bob->create(3, 'Bob', $coins);


// создание новой игры
$game = new Game;
// количество игроков может быть 2 или 3
// каждый игрок должен быть уникальным (иметь разный id)

##### Регистрация игроков #####
// игроков можно отправить в игру всех вместе одним массивом:
# $game->setPlayers([$joe, $joan, $bob]);

// или
# $players = [$joe, $joan, $bob];
# $game->setPlayers($players);

// или отправить игроков по одному:
# $game->goToGame($joe);
# $game->goToGame($joan);
# $game->goToGame($bob);

// или использовать такую запись:
$game->goToGame($joe)->goToGame($joan)->goToGame($bob);


#### Параметры игры ####
// установить количество раундов, которые будут сыграны, если у игроков закончатся монеты раньше, чем будут сыграны все раунды, игра прекратится. Если оставить пустым, то игра будет идти, пока один из игроков не выиграет все монеты у остальных
$game->setRoundAmount();

// установить размер ставки за каждый раунд, она должна быть меньше, чем количество монет на счету у игроков
$game->setBet(20);

// установить размер штрафа за сыгранный раунд вничью, эта сумма будет списана со всех игроков
$game->setDrawTax(10, 0);

// или можно задать все параметры в одну строку
# $game->setRoundAmount()->setBet(20)->setDrawTax(10, 0);

// или даже так:
# $game->goToGame($joe)->goToGame($joan)->goToGame($bob)->setRoundAmount()->setBet(20)->setDrawTax(10, 0)->start();

##### Старт игры #####
$game->start();
// или для вывода результата
# var_dump($game->start());

##### Вывод результатов и игровой статистики #####
// количество игроков:
echo 'Игроков в игре: ' . count($game->gameResult()) . '<br/>';

// размер ставки
echo 'Ставка за каждый раунд в игре: ' . $game->getBet() . '<br/>';

// размер штрафа за ничью
echo 'Штраф за ничью: ' . $game->getDrawTax() . '<br/>';

// количество указанных раундов игроком
$amount = $game->getRoundAmount() ?? 'не указано';
echo 'Заданное количество раундов: ' . $amount . '<br/>';

// вывод количества сыгранных раундов:
echo 'Раундов сыграно: ' . $game->showPlayedRound() . '<br/>';

// вывод количества сыгранных ничьих
echo 'Из них сыграно в ничью: ' . $game->drawStatistic() . '<br/>';

echo '<br /><hr /><br />';
// показать статистику по показанным фигурам:
echo 'Фигур показано: <br/>';
foreach ($game->itemsStatistic() as $id => $count) {
	echo Arm::getTitle($id) . ' => ' . $count . '<br/>';
}

// вывод турнирной таблицы, в ней на первом месте победитель(игрок с наибольшим количеством монет), на последнем - первый выбывший:
echo '<br /><hr /><br />';
$rang = 1;
foreach ($game->gameResult() as $player) {
	echo 'Место в турнирной таблице: ' . $rang++ . '<br />';
	echo 'id игрока: ' . $player->id . '<br />';
	echo 'Имя: ' . $player->name . '<br />';
	echo 'Всего игр сыграл: ' . $player->totalGame . '<br />';
	echo 'Игр выиграл: ' . $player->win . '<br />';
	echo 'Монет в конце игры: ' . $player->coins . '<br />';
	echo 'Наибольшее количество монет за игру: ' . $player->maxCoins . '<br />';
	echo '<br /><hr /><br />';
}
