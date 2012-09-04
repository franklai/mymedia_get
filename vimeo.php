<?php
require_once('curl.php');
require_once('common.php');

/**
 *
 *
 * test_url: http://vimeo.com/17917111
 */
class Vimeo
{
	private $links = array();
	private $title = 'Unknown Title';

	public function __construct($url) {
		/**
		 * 1. get content of vimeo url, 
		 *    e.g. http://vimeo.com/12392080
		 * 2. parse the content and find "signature" and "timestamp"
		 * 3. send request to http://player.vimeo.com/play_redirect?clip_id=&quality=sd&codecs=H264,VP8,VP6&sig=&time=
		 * 4. Location of 302 Found is the mp4 file url
		 * 
		 * note: 1st and 2nd request must be the same user agent.
		 */

		$videoId = $this->get_video_id($url);
		Common::debug('video id:' . $videoId);

		// 1. get html of vimeo url
		$response = new Curl($url);
		$html = $response->get_content();

		// 2. find signature and timestamp
		$signature = $this->get_signature($html);
		$timestamp = $this->get_timestamp($html);
		$isHD = $this->get_is_hd($html);
		Common::debug("signature: $signature, timestamp: $timestamp");

		// 3. send 2nd request, and get Location value
		$videoUrl = $this->get_video_url($videoId, $signature, $timestamp, $isHD);
		Common::debug("final url: $videoUrl");

		$this->links[] = $videoUrl;

		$this->title = $this->parse_title($html);
	}

	private function get_video_id($url)
	{
		$pattern = '/vimeo.com\/([0-9]+)/i';
		return Common::getFirstMatch($url, $pattern);
	}

	private function get_signature($html)
	{
		$pattern = '/"signature":"([0-9a-z]+)"/i';
		return Common::getFirstMatch($html, $pattern);
	}

	private function get_timestamp($html)
	{
		$pattern = '/"timestamp":([0-9]+)/i';
		return Common::getFirstMatch($html, $pattern);
	}

	private function get_is_hd($html)
	{
		$pattern = '<meta itemprop="videoQuality" content="HD">';
		return Common::hasString($html, $pattern);
	}

	private function get_video_url($videoId, $signature, $timestamp, $isHD)
	{
		// http://player.vimeo.com/play_redirect?clip_id=12392080&sig=e62526d8ad02d4a36f8c820df6d60eee&time=1346743364&quality=sd&codecs=H264,VP8,VP6&type=moogaloop_local&embed_location=
		$requestUrl = sprintf(
			"http://player.vimeo.com/play_redirect?clip_id=%s&sig=%s&time=%s&quality=%s&codecs=H264,VP8,VP6",
			$videoId, $signature, $timestamp, $isHD ? 'hd' : 'sd'
		);

		$response = new Curl($requestUrl);
		$location = $response->get_header('Location');

		return $location;
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
		$result = array();

		if (count($this->links) > 0) {
			$result[] = array(
				"title" => $this->title,
				"link" => $this->links[0]
			);
		}

		return $result;
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
// 	$url = 'http://vimeo.com/37974749';
// 	$url = 'http://vimeo.com/12392080';
// 	$url = 'http://vimeo.com/5606758';
// 	$url = 'http://vimeo.com/27883487';
	$url = 'http://vimeo.com/17763467';

	$host = new Vimeo($url);

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

