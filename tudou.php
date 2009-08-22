<?php
require('curl.php');

/**
 *1.http://www.tudou.com/programs/view/Veq0WIbqwa8/
 *	取得其 html ，讀出「embed src="http://www.tudou.com/v/Veq0WIbqwa8"」
 *2.http://www.tudou.com/v/Veq0WIbqwa8
 *	302 Found，在header裡有「Location: http://www.tudou.com/player/outside/player_outside.swf?iid=3838382&default_skin=http://js.tudouui.com/bin/player2/outside/Skin_outside_12.swf&autostart=false&rurl=」
 *3.http://v2.tudou.com/v2/cdn?id=3838382
 *	取其回應，會是xml，內網址數量不定
 *4.http://218.60.33.16/flv/003/838/382/903838382.flv?key=266c74469f87a55adb52704a0630a674dc2b59
 *	get it
 *
 * Note: the 3rd step and 4th step need to be the same User-Agent
 *       a solution is send step 3 User-Agent as browser user agent, using $_SERVER['HTTP_USER_AGENT']
 */
class Tudou
{
	private $links = array();
	private $title = 'Unknown Title';

	public function __construct($url) {
		// compare, 
		// http://www.tudou.com/programs/view/Veq0WIbqwa8/
		$prefix = 'http://www.tudou.com/programs/view/';
		if (0 > strncasecmp($url, $prefix, strlen($prefix))) {
			// throw exception
			return;
		}

		// get html of $tudou_url
		$response = new Curl($url);
		$html = $response->get_content();

		$pattern = '/embed src="([^"]+)"/';
		preg_match($pattern, $html, $matches);
		if (2 != count($matches)) {
			// throw exception
			return;
		}
		$view_url = $matches[1];

		$curl = new Curl($view_url);
		$location = $curl->get_header('Location');

		$pattern = '/iid=([0-9]+)/';
		preg_match($pattern, $location, $matches);
		if (2 != count($matches)) {
			// throw exception
			return;
		}
		$iid = $matches[1];

		$cdn_url = sprintf('http://v2.tudou.com/v2/cdn?id=%s', $iid);

		$response = new Curl($cdn_url, NULL, 
				array('User-Agent' => $_SERVER['HTTP_USER_AGENT']));
		$xml = $response->get_content();

		$pattern = '/>([^<]+)</';
		preg_match_all($pattern, $xml, $matches);
		foreach ($matches[1] as $item) {
			$link = $item;
			$this->links[] = $link;
		}

		// parse title
		$this->parse_title($html);
	}

	private function parse_title($html)
	{
		// don't forget GBK! encoding issue
		$html = mb_convert_encoding($html, 'UTF-8', 'GBK');
		

		$pattern = '/,kw = "([^"]+)"/';
		preg_match($pattern, $html, $matches);
		if (2 != count($matches)) {
			// error handling
			return;
		}
		$this->title = $matches[1];
	}

	public function get_title()
	{
		return $this->title;
	}

	public function get_links()
	{
		return $this->links;
	}
}
?>

