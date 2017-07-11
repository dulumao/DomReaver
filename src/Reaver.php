<?php 

namespace Reaver;

use Reaver\Link;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Database\Capsule\Manager as Capsule;

class Spider {

	protected $client;
	protected $url;
	protected $base;
	protected $args;
	protected $i;
	protected $headers;

	public function __construct()
	{
		$this->args = $_GET;
		$this->base = ['base_uri' => $this->args['--url']];
		$this->client = new Client($this->base);
		$this->url = $this->args['--url'];
		$this->i = 0;
		$this->headers = [
			'User-Agent' => 'Mozilla/5.0 (Windows NT 5.1; rv:23.0) Gecko/20100101 Firefox/23.0'
		];
	}

	public function __destruct()
	{
		
	}

	public function init() 
	{
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
			$request = new Request('GET', $url, [
				'headers' => $this->headers
			]);
			$promise = $this->client->sendAsync($request)->then(function($response) use ($request) {
				echo '('.$response->getStatusCode().') >> '.$request->getUri().PHP_EOL;
				$content = $response->getBody()->getContents();	
				$this->crawl($content, $request->getUri(), $this->base);
				$this->followed[] = $request->getUri();
			});
			
			$promise->wait();
		} catch(\GuzzleHttp\Exception\ClientException $e) {
			//
		}
	}

	private function follow()
	{
		$this->links = Link::where('read', 0)->limit(10)->get();
		//$this->fetch($this->links[$this->i++]);
		foreach ($this->links as $link) {
			$link->read = 1;
			$link->save();
			$this->fetch($link->url);
		}
	}

	private function crawl($html, $url, $base)
	{
		$crawler = new Crawler($html, $this->url);
		$links = $crawler->filterXpath('//a')->each(function(Crawler $node, $i) {
			$href = $node->link()->getUri();
			if(checkUrl($href) && !checkImage($href)) {
				$href = rtrim($href, '#');
				$href = rtrim($href, '/');
				$link = new Link;
				$link->url = $href;
				$link->expires = Carbon::now()->addDays(30);
				try {
					$link->save();
				} catch (\PDOException $e) {
					/*file_put_contents('crawl.log', '['.Carbon::now().'] ('.$e->getCode().') >> '
						.PHP_EOL, FILE_APPEND);*/
				}
			}
		});	

		if($this->hasArg('-i')) {
			$path = 'downloads/images/';
			if(!file_exists($path)) {
				mkdir($path, 0777, true);
				chmod($path, 0777);
			}
			$images = $crawler->filterXpath('//img')->each(function(Crawler $node, $i) use ($path) {
				$imageLoc = $node->attr('src');
				$imageAlt = $node->attr('alt');
				$imageLoc = preg_replace('/\\?.*/', '', $imageLoc);
				if(!checkUrl($imageLoc)) {
					$imageLoc = rtrim($this->base['base_uri'], '/') . '/' . ltrim($imageLoc, '/');
				}

				if(checkImage($imageLoc)) {
					try {
						$resource = fopen($path.basename($imageLoc), 'w');
						$stream = \GuzzleHttp\Psr7\stream_for($resource);
						$this->client->request('GET', $imageLoc, [
							'sink' => $stream
						]);
					} catch(\RuntimeException $e) {
						//
					}
				}
			});
		}

        if($this->hasArg('-f')) {
        	$this->follow();
        }
	}
}