<?php 

namespace Reaver;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Spider {

	public $url;
	public $links;

	public function __construct()
	{
		$this->url = 'http://www.mianm.com';
	}

	public function crawl()
	{

		$client = new Client(['base_uri' => $this->url]);

		$promise = $client->getAsync($this->url)->then(function($response) {
			$content = $response->getBody()->getContents();	
			$crawler = new Crawler($content);

			$title = $crawler->filterXpath('//title')->text();
			var_dump($title);

		});

		$promise->wait();
	}
}

