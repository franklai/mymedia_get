<?php
require_once('curl.php');

if (!class_exists('Common')) {
class Common {
    // return substring that match prefix and suffix
    // returned string contains prefix and suffix
    static function getSubString($string, $prefix, $suffix) {
        $start = strpos($string, $prefix);
        if ($start === FALSE) {
            echo "cannot find prefix, string:[$string], prefix[$prefix]\n";
            return $string;
        }

        $end = strpos($string, $suffix, $start);
        if ($end === FALSE) {
            echo "cannot find suffix\n";
            return $string;
        }

        if ($start >= $end) {
            return $string;
        }

        return substr($string, $start, $end - $start + strlen($suffix));
    }

    static function getFirstMatch($string, $pattern) {
        if (1 === preg_match($pattern, $string, $matches)) {
            return $matches[1];
        }
        return FALSE;
    }
}
}


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
	private $result = array();
	private $brtList = array(
		"2" => "256P",
		"3" => "360P",
		"4" => "480P",
		"5" => "720P",
		"99"=> "Original"
	);

	public function __construct($url) {
		// compare, 
		// http://www.tudou.com/programs/view/Veq0WIbqwa8/

		$response = new Curl($url);
		$html = $response->get_content();


		$pattern = '/iid = ([0-9]+)/';
		$iid = Common::getFirstMatch($html, $pattern);
		if (FALSE === $iid) {
			return;
		}

		$videoListUrl = sprintf('http://v2.tudou.com/v.action?st=2,3,4,5,99&it=%s', $iid);

		$response = new Curl($videoListUrl);
		$xml = $response->get_content();

		$videoList = $this->parseXml($xml);
	}

	private function parseXml($xml)
	{
		$list = array();

		$decodedXml = htmlspecialchars_decode($xml);

		$title = $this->parse_title($decodedXml);

// 		echo $decodedXml;
// 		echo str_replace(">", ">\n", $decodedXml);

		$pattern = '/brt="([0-9]+)">(http:[^<]+)</';
		$ret = preg_match_all($pattern, $decodedXml, $matches, PREG_SET_ORDER);

		if (FALSE === $ret) {
			return $list;
		}

		if ($ret > 0) {
			foreach($matches as $match) {
				$brt = $match[1];
				$videoUrl = $match[2];
				$this->result[] = array(
					"title" => $this->getConvertedTitle($title, $brt),
					"link" => $videoUrl
				);
			}
		}
	}

	private function parse_title($xml)
	{
		$title = 'Unknown title';
		$pattern = '/title="([^"]+)"/';

		$ret = Common::getFirstMatch($xml, $pattern);
		if (FALSE === $ret) {
			return $title;
		}

		$title = $ret;

		return $title;
	}

	private function getConvertedTitle($title, $brt)
	{
		$ret = $title . $this->getFormatString($brt);

		return $ret;
	}

	private function getFormatString($brt)
	{
		if (array_key_exists($brt, $this->brtList)) {
			return sprintf(" (%s)", $this->brtList[$brt]);
		} else {
			return sprintf(" (Unknown brt %s)", $brt);
		}
	}

	public function get_title()
	{
		return $this->title;
	}

	public function get_links()
	{
		return $this->links;
	}

	public function get_result()
	{
		return $this->result;
	}
}

if (basename($argv[0]) === basename(__FILE__)) {
// 	$url = 'http://www.tudou.com/programs/view/YwGhpN2C_KI/';
// 	$url = 'http://www.tudou.com/programs/view/ogyN7FvbENM/?fr=rec1';
// 	$url = 'http://www.tudou.com/listplay/GbbmprNY16w/WnMfkxSleL4.html';
// 	$url = 'http://www.tudou.com/programs/view/UHcWm65Nt-c/';
	$url = 'http://www.tudou.com/programs/view/7dWtoMMsaYE/';
// 	$url = 'http://www.tudou.com/programs/view/Xohot2RSQJw/';

	$tudou = new Tudou($url);

	$result = $tudou->get_result();

	$count = count($result);

	echo "count is $count\n";

	for ($idx = 0; $idx < $count; $idx++) {
		$title = $result[$idx]['title'];
		$link = $result[$idx]['link'];

		printf("\n%s:\n", $title);
		printf("\t%s\n", substr($link, 0, strpos($link, "?")));
	}
}
?>

