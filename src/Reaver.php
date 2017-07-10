<?php 

namespace Reaver;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DomCrawler\Crawler;

class Spider {

	protected $client;
	protected $url;
	protected $links = [];
	protected $base;
	protected $args;
	protected $i;

	public function __construct()
	{
		$this->args = $_GET;
		array_push($this->links, $this->args['--url']);
		$this->base = ['base_uri' => $this->args['--url']];
		$this->client = new Client($this->base);
		$this->url = $this->args['--url'];
		$this->i = 0;
	}

	public function __destruct()
	{
		
	}

	public function init() {
		
		$this->fetch($this->url);
	}

	private function hasArg($arg) 
	{
		if(array_key_exists($arg, $this->args)) {
			return true;
		} 
		return false;
	}

	private function fetch($url)
	{
		try {
			$request = new Request('GET', $url);
			$promise = $this->client->sendAsync($request)->then(function($response) use ($request) {
				echo '['.Carbon::now().'] ('.$response->getStatusCode().') >> '.$request->getUri().PHP_EOL;
				$content = $response->getBody()->getContents();	
				$this->crawl($content, $request->getUri(), $this->base);
				$this->followed[] = $request->getUri();
			});
			
			$promise->wait();
		} catch(\GuzzleHttp\Exception\ClientException $e) {
			var_dump($e->getMessage());
		}
	}

	private function follow()
	{
		$this->fetch($this->links[$this->i++]);
	}

	private function crawl($html, $url, $base)
	{
		$crawler = new Crawler($html, $this->url);
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
        if($this->hasArg('-f')) {
        	$this->follow();
        }
	}
}