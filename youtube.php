<?php
require_once('curl.php');
require_once('common.php');

class YouTube
{
	private $links = array();
	private $title = 'Unknown Title';
	private $result = array();

	// http://en.wikipedia.org/wiki/YouTube#Quality_and_codecs
	private $itagMap = array(
		"5"  => "240p, FLV, H.263, MP3",
		"18" => "360p, MP4, H.264, AAC",
		"22" => "720p, MP4, H.264, AAC",
		"34" => "360p, FLV, H.264, AAC",
		"35" => "480p, FLV, H.264, AAC",
		"37" => "1080p, MP4, H.264, AAC"
	);

	public function __construct($url) {
		/**
		 * 1. get content of YouTube url, 
		 *    e.g. http://www.youtube.com/watch?v=5kWFGTH8K5g
		 * 2. parse the content and find "url_encoded_fmt_stream_map"
		 * 
		 */

		// 1. get html of vimeo url
		$response = new Curl($url);
		$html = $response->get_content();

		// 2. find url_encoded_fmt_stream_map
		$encodedMapString = $this->get_map_string($html);
		$mapString = urldecode($encodedMapString);

		// 3. parse map string
		$videoMap = $this->parse_map_string($mapString);

		// title
		$this->title = $this->parse_title($html);
		$this->set_result($videoMap, $this->title);
	}

	private function set_result($videoMap, $title)
	{
		$inItagMapping = array();
		$noItagMapping = array();

		foreach ($videoMap as $itag => $url) {
			// deprecated variable $this->links
			$this->links[] = $url;

			$item = array(
				"title"=> $this->get_title_by_itag($title, $itag),
				"link" => $url
			);
			if ($this->has_itag_mapping($itag)) {
				$inItagMapping[] = $item;
			} else {
				$noItagMapping[] = $item;
			}
		}

		$this->result = array_merge($inItagMapping, $noItagMapping);
	}

	private function has_itag_mapping($itag)
	{
		return array_key_exists($itag, $this->itagMap);
	}

	private function get_title_by_itag($title, $itag)
	{
		$itagString = "itag value $itag";

		if (array_key_exists($itag, $this->itagMap)) {
			$itagString = $this->itagMap[$itag];
		}

		return sprintf("%s (%s)", $title, $itagString);
	}

	private function parse_map_string($mapString)
	{
		$requiredKeys = array('itag', 'url', 'sig');

		$urlList = explode(',', $mapString);

		$videoMap = array();

		foreach ($urlList as $url) {
			$blValid = TRUE;

			if (empty($url)) {
				continue;
			}

			$queries = str_replace('\\u0026', '&', $url);

			parse_str($queries, $items);

			foreach ($requiredKeys as $key) {
				if (FALSE === array_key_exists($key, $items)) {
					$blValid = FALSE;
					break;
				}
			}

			if ($blValid === FALSE) {
				continue;
			}

			$videoMap[$items['itag']] = $items['url'] . "&signature=" . $items['sig'];
		}

		return $videoMap;
	}

	private function get_map_string($html)
	{
		$pattern = '/"url_encoded_fmt_stream_map": "([^"]+)"/';
		return Common::getFirstMatch($html, $pattern);
	}

	private function parse_title($html)
	{
		// property="og:title" content="Don&#039;t Look Back in Anger"
		$pattern = '/property="og:title" content="([^"]+)"/';
		$encodedTitle = Common::getFirstMatch($html, $pattern);
		return htmlspecialchars_decode($encodedTitle, ENT_QUOTES);
	}

	public function get_result()
	{
		return $this->result;
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

if (!empty($argv) && basename($argv[0]) === basename(__FILE__)) {
// 	$url = 'http://www.youtube.com/watch?v=5kWFGTH8K5g';
// 	$url = 'http://www.youtube.com/watch?v=KZyxFbx00TI';

	// 2012 Sep 14
	$url = 'http://www.youtube.com/watch?v=JtpvfeKHsTk';
// 	$url = 'http://www.youtube.com/watch?v=qpD2LOSF6R0';

	$host = new YouTube($url);

	$result = $host->get_result();

	$count = count($result);

	echo "count is $count\n";

	for ($idx = 0; $idx < $count; $idx++) {
		$title = $result[$idx]['title'];
		$link = $result[$idx]['link'];

		printf("\n%s:\n", $title);
		printf("\t%s\n", $link);
	}
}

?>

