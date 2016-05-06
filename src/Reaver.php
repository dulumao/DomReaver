<?php 

namespace Reaver;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Spider {

	public $url;
	public $links;
	public $base;
	public $site;

	public function __construct()
	{
		echo '['.Carbon::now().'] Initializing Reaver...'.PHP_EOL;
	}

	public function __destruct()
	{
		//var_dump($this->site);
	}

	public function setUrl($url)
	{
		$this->url = $url;
		$this->base = ['base_uri' => $this->url];
	}

	public function fetch()
	{
		$client = new Client($this->base);

		$promise = $client->getAsync($this->url)->then(function($response) {

			echo '['.Carbon::now().'] ('.$response->getStatusCode().') >> '.$this->url.PHP_EOL;

			$content = $response->getBody()->getContents();	

			$this->crawl($content);
		});

		$promise->wait();
	}	

	public function crawl($html)
	{
		$crawler = new Crawler($html, $this->url);
		$title = count($crawler->filterXPath('//title')) != 0 ? $crawler->filterXPath('//title')->text() : $this->url;

        $metas = $crawler->filterXPath('//meta[@name="description"]');

        $meta = count($metas) !== 0 ? $crawler->filterXPath('//meta[@name="description"]')->attr('content') : '';
        $meta = !empty($meta) ? $meta : truncate($crawler->filterXPath('//body')->text());

        $this->site = [
        	'url' => $this->url,
        	'base' => $this->base, 
        	'title' => $title, 
        	'description' => $description, 
        	'html' => $crawler->html()
        ];

		$links = $crawler->filterXpath('//a')->each(function(Crawler $node, $i) {
			$href = url_to_absolute($this->url, $node->attr('href'));
			$href = rtrim($href, '#');
			$href = rtrim($href, '/');
			$this->links[] = $href;
		});	
	}

	public function run()
	{
		$this->fetch();
	}

}

