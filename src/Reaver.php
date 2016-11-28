<?php 

namespace Reaver;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Symfony\Component\DomCrawler\Crawler;

class Spider {

	protected $url;
	protected $links;
	protected $base;
	protected $site;
	protected $followed;
	protected $robots;
	protected $html;

	/**
	 * Sets the base or seed url to crawl
	 * @param string
	 */
	public function setUrl($url)
	{
		$this->url = $url;
		$this->base = ['base_uri' => $this->url];
	}

	/**
	 * Fetches the base url and sends the contents, url and base
	 * url to the crawl method
	 */
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


	/**
	 * Uses the symfony Dom Crawler library to extract the Title
	 * description and stripped content of the page.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return array
	 */
	public function crawl($html, $url, $base)
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
	public function follow()
	{
		foreach($this->links as $link) {
			if(in_array($link, $this->followed)) continue;
			$this->url = $link;
			$this->fetch();
		}
	}

	/**
	 * TODO: set up crawling stats to echo out on finish.
	 * @return [type]
	 */
	public function stats()
	{
		var_dump($this->followed, $this->site);
	}

}

