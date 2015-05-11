<?php
namespace cyberinferno;

class DotabuffScraper
{
	public $heroList = [];
	public $heroBg = [];
	public $heroRole = [];

	private $_heroesUrl = 'http://www.dotabuff.com/heroes';
	private $_patch;
	private $_errors = [];

	/**
	 * Constructor allowing to specify patch version
	 * @param string $patch Patch string for which information has to be scraped
	 */
	function __construct($patch = 'patch_6.83c')
	{
		$this->_patch = $patch;
	}

	/**
	 * Scrapes hero ID, hero background image, hero roles and hero names of all available heroes
	 * @return bool status of scraping
	 */
	public function scrapeHeroesData()
	{
		$content = $this->getRequest($this->_heroesUrl);
		if(!$content) {
			return false;
		}
		$xpath = $this->getExpath($content);
		$heroGrid = $xpath->query("//div[@class='hero-grid']")->item(0);
		if (!is_null($heroGrid)) {
			$childNodes = $heroGrid->childNodes;
			foreach ($childNodes as $node) {
				$heroId = substr($node->getAttribute('href'), 8);
				$roles = explode(' ', $node->getAttribute('class'));
				$this->heroList[$heroId] = $node->nodeValue;
				$style = $node->childNodes->item(0)->getAttribute('style');
				$this->heroBg[$heroId] = substr($style, 16, strlen($style) - 17);
				$this->heroRole[$heroId] = $roles;
			}
			return true;
		}
		return false;
	}

	/**
	 * Scrapes matchups for each hero with all available heroes
	 * @todo Do HTML processing and saving of data
	 * @return bool status of scraping
	 */
	public function scrapeWinRates()
	{
		foreach ($this->heroList as $key => $value) {
			$dataUrl = $this->_heroesUrl.'/'.$key.'/matchups?date='.$this->_patch;
			$content = $this->getRequest($dataUrl);
			if(!$content) {
				return false;
			}
			$xpath = $this->getExpath($content);
			$tableBody = $xpath->query("//table")->item(1);
			if (!is_null($heroGrid)) {
				
			}
		}
		return false;
	}

	/**
	 * Does a Curl get request to the given URL
	 * @param  string $url URL for which the request has to be made
	 * @return string/bool HTML content of the request if the request was successfull else FALSE
	 */
	private function getRequest($url)
	{
		$content = false;
		if(extension_loaded('curl')) {
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $url);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Dota2');
			$content = curl_exec($curl_handle);
			if(curl_errno($curl_handle)) {
				$this->addError($url.' fetching error: '.curl_error($curl));
			}
			curl_close($curl_handle);
		} else {
			$this->addError('php-curl extension is required for this class');
		}
		return $content;
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
	 * Returns XPath object for the HML content
	 * @param  string $content HTML string
	 * @return DOMXpath XPath object
	 */
	private function getXpath($content)
	{
		$doc = new DOMDocument;
		libxml_use_internal_errors(true);
		if(!$doc->loadHTML($content)) {
			$error = '';
			foreach (libxml_get_errors() as $error) {
				$error .= $error->message.' | ';
			}
			libxml_clear_errors();
			$this->addError('Load HTML error: '.$error);
			return false;
		}
		$xpath = new DOMXpath($doc);
		return $xpath;
	}
}