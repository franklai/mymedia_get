<?php
// require('curl.php');
require('tudou.php');

function MSG($message)
{
	$destination = './log.txt';
	$func_info = sprintf('%s() at %s:%d', xdebug_call_function(), 
						xdebug_call_file(), xdebug_call_line()); 

	error_log(date('Y-m-d G:i:s'), 3, $destination);
	error_log(' '.$func_info."\n", 3, $destination);
	error_log(var_export($message, TRUE)."\n", 3, $destination);
}

function main()
{
	$url = NULL;

	// get user input
	if (isset($_GET['url'])){
		$url = $_GET['url'];
	}

	$value = media_url_parse($url);

	// encode it to JSON
	$output = json_encode($value);

	// print JSON string to browser
	print($output);
}

function media_url_parse($url)
{
	$result = array(array('link'=> NULL, 'title'=> NULL));

	if (is_null($url)) {
		return $result;
	}

	$array = parse_url($url);

	if (isset($array['host'])) {
		$host = $array['host'];
	}
	else {
		return $result;
	}

	if (stristr($host, '.youtube.com')) {
		$result = url_handler_youtube($url);
	} else if (stristr($host, 'mymedia.yam.com')) {
		$result = url_handler_mymedia($url);
	} else if (stristr($host, '.tudou.com')) {
		$result = url_handler_tudou($url);
	}

	return $result;
}

/**
 * url handlers
 */
function url_handler_tudou($url)
{
	$result = array();

	$tudou = new Tudou($url);
	$links = $tudou->get_links();
	$title = $tudou->get_title();

	foreach ($links as $link) {
		$result[] = array('link' => $link, 'title' => $title);
	}

	return $result;
}

function url_handler_mymedia($url)
{
	$result = array();
	
	if (!isset($url)) {
		return $result;
	}

	function get_title_by_url($url)
	{
		$title = NULL;
		$pattern = '/<title>yam 天空部落-影音分享-([^<]+)<\/title/';

		$html = url_get_html($url);

		preg_match($pattern, $html, $matches);
		if (2 != count($matches)) {
			return $title;
		}
		$title = $matches[1];

		return $title;
	}

	function get_link_by_url($url)
	{
		$id = NULL;
		$link = NULL;

		if (empty($url)) {
			return $link;
		}

		// first, parse ID
		$pattern = '/\/m\/([0-9]+)/';
		preg_match($pattern, $url, $matches);

		if (2 != count($matches)) {
			return $link;
		}
		$id = $matches[1];

		$link_prefix = 'http://mymedia.yam.com/api/a/?pID=';

		$html = url_get_html($link_prefix . $id);

		$pattern = '/(mp3file|furl)=([^&]+)&/';
		preg_match($pattern, $html, $matches);
		if (3 != count($matches)) {
			return $link;
		}

		$link = $matches[2];

		return $link;
	}
	$link = get_link_by_url($url);
	$ext = strrchr($link, '.') ;

	$result[0] = array('link' => $link,
					   'title' => get_title_by_url($url).$ext);
	return $result;
}


// function url_handler_youtube($url)
// {
// 	// 1. check url
// 	// 2. re
// }

function url_handler_youtube($url)
{
	$result = array();

	if (!isset($url)) {
		return $result;
	}

	function get_links_by_html($html) {
		$links = array('link' => NULL, 'hq_link' => NULL);

		if (empty($html)) {
			return $links;
		}

		$pattern = '/"video_id": "([^"]+)".*"t": "([^"]+)"/';
		preg_match($pattern, $html, $matches);
		if (3 != count($matches)) {
			return $links;
		}

		$youtube_link = sprintf('http://%s/get_video?video_id=%s&t=%s',
						 'www.youtube.com', $matches[1], $matches[2]);
		$links['link'] = url_get_header($youtube_link.'&fmt=34', 'Location');
		$links['hq_link'] = url_get_header($youtube_link.'&fmt=18', 'Location');

		return $links;
	}

	function get_title_by_html($html) {
		$title = NULL;

		if (empty($html)) {
			return $title;
		}

		$pattern = '/<meta name="title" content="([^"]+)">/';
		preg_match($pattern, $html, $matches);
		if (2 != count($matches)) {
			return $title;
		}
		$title = $matches[1];

		return $title;
	}

	$html = url_get_html($url);
	$title = get_title_by_html($html);
	$links = get_links_by_html($html);
	$result[0] = array('title' => $title.'.flv', 'link' => $links['link']);
	$result[1] = array('title' => $title.'.mp4', 'link' => $links['hq_link']);

	return $result;
}

/**
 * utility functions
 */
function url_get_header($url, $key) {
	$obj = new Curl($url);
	$headers = $obj->get_info();
	return $headers[$key];
}
function url_get_html($url) {
	$obj = new Curl($url);
	return $obj->get_content();
}


// execute main function
main();
?>


