<?php 

namespace Reaver;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Spider {

	public $url;
	public $links;

	public function __construct()
	{
		$this->url = 'http://www.twitter.com';
	}

	public function __destruct()
	{
		var_dump($this->links);
	}

	public function crawl()
	{
		$client = new Client(['base_uri' => $this->url]);

		$promise = $client->getAsync($this->url)->then(function($response) {
			$content = $response->getBody()->getContents();	
			$crawler = new Crawler($content, $this->url);

			$title = $crawler->filterXpath('//title')->text();
			$links = $crawler->filterXpath('//a')->each(function(Crawler $node, $i) {
				echo $node->attr('href') . PHP_EOL;

				$text = trim($node->text());
				$href = url_to_absolute($this->url, $node->attr('href'));
				$href = rtrim($href, '#');
				$href = rtrim($href, '/');

				$this->links[] = [
					$text => $href
				];
			});

		});

		$promise->wait();
	}
}

