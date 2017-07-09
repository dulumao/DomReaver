<?php 

namespace Reaver;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
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
		var_dump($this->site);
	}

	public function setUrl($url) {
		$this->links[] = $url;
		$this->base = ['base_uri' => $url];
	}

	public function init() 
	{
		$this->fetch();
		$this->follow();
	}

	private function follow()
	{
		var_dump($this->links);
	}

	private function fetch()
	{
		foreach ($this->links as $link) {
		    $this->promises[] = $this->client->requestAsync('GET', $link);
			echo '['.Carbon::now().'] Sending request for '.$link.PHP_EOL;
		    $this->url = $link;
		}

		\GuzzleHttp\Promise\all($this->promises)->then(function (array $responses) {
		    foreach ($responses as $response) {
		    	echo '['.Carbon::now().'] Request Received '.PHP_EOL;
		        echo '['.Carbon::now().'] ('.$response->getStatusCode().') >> '.$this->url.PHP_EOL;
				$content = $response->getBody()->getContents();	
				$this->crawl($content, $this->url, $this->base);
				$this->followed[] = $this->url;
		    }
		})->wait();
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