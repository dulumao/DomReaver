<?php 

namespace Reaver;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DomCrawler\Crawler;

class Spider {

	protected $client;
	protected $promise;
	protected $promises;
	protected $url;
	protected $links;
	protected $base;
	protected $site;
	protected $followed;
	protected $robots;
	protected $html;

	public function __construct()
	{
		$this->client = new Client();
	}

	public function __destruct()
	{
		
	}

	public function init() {
		//$this->links[] = $url;
		//$this->base = ['base_uri' => $url];
		var_dump($_GET);
	}

	private function follow()
	{
		$this->fetch();
	}

	private function fetch()
	{
		foreach($this->links as $link) {
			$request = new Request('GET', $link);
			$promise = $this->client->sendAsync($request)->then(function($response) use ($link) {
				echo '['.Carbon::now().'] ('.$response->getStatusCode().') >> '.$link.PHP_EOL;
				//$content = $response->getBody()->getContents();	
				//var_dump($content);
			});
		}
		$promise->wait();
	}

	private function crawl($html, $url, $base)
	{
		$crawler = new Crawler($html, $this->url);
		$title = count($crawler->filterXPath('//title')) != 0 ? $crawler->filterXPath('//title')->text() : $this->url;
        $metas = $crawler->filterXPath('//meta[@name="description"]');
        $meta = count($metas) !== 0 ? $crawler->filterXPath('//meta[@name="description"]')->attr('content') : '';
        $meta = !empty($meta) ? $meta : substr($crawler->filterXPath('//body')->text(), 0, 200);

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
}