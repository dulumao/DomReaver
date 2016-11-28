<?php 

namespace Reaver;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Symfony\Component\DomCrawler\Crawler;

class Spider {

	public $url;
	public $links;
	public $base;
	public $site;
	public $followed;

	public function __construct()
	{
		echo '['.Carbon::now().'] Initializing Reaver...'.PHP_EOL;
	}

	public function __destruct()
	{
		echo '['.Carbon::now().'] Finalizing Crawl...'.PHP_EOL;
		echo '['.Carbon::now().'] Stats:'.PHP_EOL;
		echo '------------------------------------------'.PHP_EOL;
		echo 'Found Links:'.count($this->links) . PHP_EOL;
		echo 'Crawled Pages:'.count($this->followed) . PHP_EOL;
		$this->site = json_encode($this->site);
        $this->site = indent($this->site);
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

			$this->crawl($content, $this->url, $this->base);
			$this->followed[] = $this->url;
		});

		$promise->wait();
	}	

	public function crawl($html, $url, $base)
	{
		$crawler = new Crawler($html, $this->url);
		$title = count($crawler->filterXPath('//title')) != 0 ? $crawler->filterXPath('//title')->text() : $this->url;

        $metas = $crawler->filterXPath('//meta[@name="description"]');

        $meta = count($metas) !== 0 ? $crawler->filterXPath('//meta[@name="description"]')->attr('content') : '';
        $meta = !empty($meta) ? $meta : truncate($crawler->filterXPath('//body')->text());

        $this->site[] = [
        	'url' => $url,
        	'base' => $base, 
        	'title' => $title, 
        	'description' => $meta, 
        	'html' => preg_replace('/(\s)+/', ' ', strip_tags($crawler->html()))
        ];

		$links = $crawler->filterXpath('//a')->each(function(Crawler $node, $i) {
			$href = url_to_absolute($this->url, $node->attr('href'));
			if(checkUrl($href) && !checkImage($href) && !is_null($href)) {
				$href = rtrim($href, '#');
				$href = rtrim($href, '/');
				$this->links[] = $href;
			}
		});	

		$this->links = is_array($this->links) ? array_unique($this->links) : [$this->links];
        $this->links = array_filter($this->links);
        $this->links = array_values($this->links);
	}

	public function init()
	{
		$this->fetch();
		foreach($this->links as $link) {
			if(in_array($link, $this->followed)) continue;
			$this->url = $link;
			$this->fetch();
		}
	}

	public function run()
	{
		$this->init();
	}

}

