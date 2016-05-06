<?php 

namespace Reaver;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
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

		$client = new Client();

		$request = new Request('GET', $this->url);

		$promise = $client->sendAsync($request)->then(function ($response) {
		    echo 'I completed! ' . $response->getBody();
		});
		$promise->wait();

		/*
		$client = new Client();

		$crawler = $client->request('GET', 'http://www.symfony.com/blog/');*/
	}
}

