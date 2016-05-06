<?php 

namespace Reaver;

use Carbon\Carbon;
use GuzzleHttp\Pool;
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
	public $followed;

	public function __construct()
	{
		echo '['.Carbon::now().'] Initializing Reaver...'.PHP_EOL;
	}

	public function __destruct()
	{

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
        	'description' => $meta, 
        	'html' => $crawler->html()
        ];

        $this->site = json_encode($this->site);
        $this->site = indent($this->site);

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

		if(!file_exists('index.json')) {
			exec('touch index.json');
		}

		file_put_contents('index.json', $this->site, FILE_APPEND | LOCK_EX);

		$this->followed[] = $this->url;
	}

	public function follow()
	{

		$client = new Client($this->base);

		foreach($this->links as $link) {
			$promises[] = $client->getAsync($link)->then(function($response) use($link) {
				echo '['.Carbon::now().'] ('.$response->getStatusCode().') >> '.$link.PHP_EOL;

				$content = $response->getBody()->getContents();	

				$this->crawl($content);
			});
		}

		$results = Promise\settle($promises)->wait();


	}

	public function run()
	{
		$this->fetch();
		$this->follow();
	}

}

