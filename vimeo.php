<?php
require_once('curl.php');

/**
 *
 *
 * test_url: http://vimeo.com/17917111
 */
class Vimeo
{
	private $links = array();
	private $title = 'Unknown Title';
	private $request_signature = '';
	private $request_signature_expires = '';

	public function __construct($url) {
		$pattern = '/vimeo.com\/([0-9]+)/';
		preg_match($pattern, $url, $matches);
		if (2 != count($matches)) {
			// throw exception
			return;
		}
		$video_id = $matches[1];

		$xml_url = sprintf('http://vimeo.com/moogaloop/load/clip:%s/local', $video_id);
		$response = new Curl($xml_url);
		$xml = $response->get_content();

		$this->parse_xml($xml);
	}

	private function parse_xml($xml) {
		$doc = new DOMDocument();
		if (!$doc->loadXML($xml)) {
			throw new Exception('Failed to load XML.');
		}

		$request_signature = $this->getFirstItemValue($doc, 'request_signature');
		$request_signature_expires = $this->getFirstItemValue($doc, 'request_signature_expires');
		$is_hd = $this->getFirstItemValue($doc, 'isHD');
		$clip_id = $this->getFirstItemValue($doc, 'clip_id');
		$caption = $this->getFirstItemValue($doc, 'caption');

		$clip_url_prefix = sprintf('http://vimeo.com/moogaloop/play/clip:%s/%s/%s/',
				$clip_id, $request_signature, $request_signature_expires);
		$clip_url = sprintf('%s?q=%s', $clip_url_prefix, $is_hd ? 'hd' : 'sd');

		$curl = new Curl($clip_url);
		$location = $curl->get_header('Location');
		$this->links[] = $location;

		$this->title = $caption;
	}

	private function getFirstItemValue($doc, $tagName) {
		$nodeList = $doc->getElementsByTagName($tagName);
		if ($nodeList->length !== 1) {
			return FALSE;
		}
		return $nodeList->item(0)->nodeValue;
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

