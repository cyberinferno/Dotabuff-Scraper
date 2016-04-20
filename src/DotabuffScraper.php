<?php
namespace cyberinferno\dotabuffscraper;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class DotabuffScraper A simple tool to scrape Dotabuff website for information
 * @package cyberinferno\dotabuffscraper
 */
class DotabuffScraper
{
	const BASE_URL				= 'http://www.dotabuff.com';
	const HEROES_URL			= 'http://www.dotabuff.com/heroes';

	private $_heroList = [];
	private $_patch;
	private $_errors = [];
	private $_client;

	/**
	 * Constructor allowing to specify patch version
	 * @param string|null $patch Patch string for which information has to be scraped
	 * @throws \Exception
	 */
	public function __construct($patch = null)
	{
		$this->_patch = $patch;
		$this->_client = new Client();
	}

	/**
	 * Checks connectivity to Dotabuff website
	 * @return bool
     */
	private function canConnectToDotabuff()
	{
		try {
			$crawler = $this->_client->request('GET', self::BASE_URL);
			$title = $crawler->filterXPath('//title')->text();
			return !(strpos($title, 'DOTABUFF') === false);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Returns all hero data list
	 * @return array Key value pair of heroes
     */
	public function getHeroList()
	{
		$this->clearPreviousErrors();
		if (!$this->canConnectToDotabuff()) {
			$this->addError('Could not connect to Dotabuff server.
			Please make sure you are connected to the internet and your firewall is not blocking Dotabuff website!');
			return [];
		}
		if (!is_array($this->_heroList) || count($this->_heroList) === 0) {
			$crawler = $this->_client->request('get', self::HEROES_URL);
			$heroData = [];
			try {
				$heroDivContents = $crawler->filter('div.hero-grid')->children();
				$heroDivContents->filter('a')->each(function (Crawler $node, $i) {
					$heroData[end(explode('/', $node->attr('href')))] = $node->text();
				});
				$this->_heroList = $heroData;
				return $this->_heroList;
			} catch (\Exception $e) {
				$this->addError(get_class($e) . ': ' . $e->getMessage());
				return [];
			}
		}
		return $this->_heroList;
	}

	/**
	 * Returns extra hero information
	 * @param $heroKey String Hero key whose info has to be fetched
	 * @return array
     */
	public function getHeroInfo($heroKey)
	{
		$this->clearPreviousErrors();
		if (!$this->canConnectToDotabuff()) {
			$this->addError('Could not connect to Dotabuff server.
			Please make sure you are connected to the internet and your firewall is not blocking Dotabuff website!');
			return [];
		}
		$crawler = $this->_client->request('get', self::HEROES_URL.'/'.$heroKey);
		try {
			if ($crawler->filter('span.won')->count() !== 0) {
				$winRate = $crawler->filter('span.won')->first()->text();
			} else {
				$winRate =$crawler->filter('span.lost')->first()->text();
			}
			return [
				'roles' => $crawler->filter('small')->first()->text(),
				'popularity' => $crawler->filter('dd')->first()->text(),
				'win_rate' => $winRate
			];
		} catch (\Exception $e) {
			$this->addError(get_class($e) . ': ' . $e->getMessage());
			return [];
		}
	}

	public function getHeroMatchup($heroKey)
	{
		$this->clearPreviousErrors();
		if (!$this->canConnectToDotabuff()) {
			$this->addError('Could not connect to Dotabuff server.
			Please make sure you are connected to the internet and your firewall is not blocking Dotabuff website!');
			return [];
		}
		$crawler = $this->_client->request('get', self::HEROES_URL.'/'.$heroKey.'/matchups');
		try {
			$matchupData = [];
			$tbody = $crawler->filter('tbody')->first()->children();
			$tbody->filter('tr')->each(function (Crawler $node, $i) use($matchupData){
				$data = ['hero' => end(explode('/', $node->attr('data-link-to')))];
				$node->filter('td')->each(function (Crawler $n, $i) use($data){
					switch ($i) {
						case 2:
							$data['advantage'] = $n->attr('data-value');
							break;
						case 3:
							$data['win_rate'] = $n->attr('data-value');
							break;
					}
				});
				$matchupData[] = $data;
			});
			return $matchupData;
		} catch (\Exception $e) {
			$this->addError(get_class($e) . ': ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Inserts into error messages array
	 * @param string $errorMsg Error message to be inserted
	 */
	private function addError($errorMsg)
	{
		$this->_errors[] = $errorMsg;
	}

	/**
	 * Returns error messages array
	 * @return array All errors
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * Returns whether error has occurred
	 * @return bool
     */
	public function hasError()
	{
		return count($this->_errors) !== 0;
	}

	/**
	 * Clears previously set errors
     */
	private function clearPreviousErrors()
	{
		$this->_errors = [];
	}
}