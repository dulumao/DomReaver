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

	/**
	 * Sets the base or seed url to crawl
	 * @param string
	 */
	public function setUrl($url)
	{
		$this->url[] = $url;
		$this->base = ['base_uri' => $this->url];
	}

	public function init() 
	{
		$this->sendRequests();	
	}

	private function handleResponse($response)
	{
		echo '['.Carbon::now().'] ('.$response->getStatusCode().') >> '.$this->url.PHP_EOL;
		$content = $response->getBody()->getContents();	
		$this->crawl($content, $this->url, $this->base);
		$this->followed[] = $this->url;
	}

	private function sendRequests()
	{
		$this->promise = $this->client->requestAsync('GET', $this->url);
		$this->promise->then(function($response) {
			$this->handleResponse($response);
		});
		$this->fetch();
	}

	/**
	 * Fetches the base url and sends the contents, url and base
	 * url to the crawl method
	 */
	private function fetch()
	{
		try {			
			$this->promise->wait();
		} catch(\GuzzleHttp\Exception\ClientException $e) {
			echo $e->getMessage();
		}
	}	

	/**
	 * Uses the symfony Dom Crawler library to extract the Title
	 * description and stripped content of the page.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return array
	 */
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

	/**
	 * Follows links
	 * TODO: will set up a method for abiding by robots directives
	 * @return [type]
	 */
	private function follow()
	{
		foreach($this->links as $link) {
			if(in_array($link, $this->followed)) continue;
			$this->promises[] = $this->client->requestAsync('GET', $link);
		}

		\GuzzleHttp\Promise\all($this->promises)->then(function(array $responses) {
		    foreach ($responses as $response) {
		        $this->handleResponse($response);
		    }
		})->wait();
	}

	/**
	 * TODO: set up crawling stats to echo out on finish.
	 * @return [type]
	 */
	private function stats()
	{
		var_dump($this->followed, $this->site);
	}

}

