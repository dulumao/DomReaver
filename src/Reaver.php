<?php 

namespace Reaver;

use Symfony\Component\DomCrawler\Crawler;

class Spider {

	public $url;
	public $links;

	public function __construct()
	{
		$this->url = 'http://www.mianm.com';
	}

	public function crawl()
	{

		$crawler = new Crawler;

		$crawler->registerNamespace('m', $this->url);

		var_dump($crawler);

		echo "Hello Crawler";
	}
}

