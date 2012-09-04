<?php
// require('curl.php');
require('tudou.php');
require('vimeo.php');
require('youtube.php');

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
	} else if (stristr($host, 'vimeo.com')) {
		$result = url_handler_vimeo($url);
	}

	return $result;
}

/**
 * url handlers
 */
function url_handler_youtube($url)
{
	$result = array();

	$vimeo = new YouTube($url);

	$result = $vimeo->get_result();

	return $result;
}

function url_handler_vimeo($url)
{
	$result = array();

	$vimeo = new Vimeo($url);
	$links = $vimeo->get_links();
	$title = $vimeo->get_title();

	foreach ($links as $link) {
		$result[] = array('link' => $link, 'title' => $title);
	}

	return $result;
}

function url_handler_tudou($url)
{
	$result = array();

	$tudou = new Tudou($url);

	$result = $tudou->get_result();

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

function out_of_dated_url_handler_youtube($url)
{
	$result = array();

	if (!isset($url)) {
		return $result;
	}

	function get_links_by_html($html) {
		$links = array();

		if (empty($html)) {
			return $links;
		}

        // parse links with fmt_url_map
        // "fmt_url_map": "[^"]+"
		$pattern = '/fmt_url_map": "([^"]+)"/';
		$url_pattern = '/,?([0-9]+\|)/';
		if (1 == preg_match($pattern, $html, $matches)) {
			$url_map = str_replace('\\/', '/', urldecode($matches[1]));

			$replaced_url_map = preg_replace($url_pattern, '@@$1', $url_map);

			$urls = explode('@@', $replaced_url_map);
			array_shift($urls);
			$links = $urls;
		}
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

    // at least three headers
    // VISITOR_INFO1_LIVE
    // GEO
    // PREF

	$html = url_get_html($url);
	$title = get_title_by_html($html);
	$links = get_links_by_html($html);
    foreach ($links as $key=> $link) {
        $items = explode('|', $link);
        if (2 == count($items)) {
            // other format link
            $fmt = $items[0];

			switch ($fmt) {
				case '5':
					$t = $title.'.flv (Size: 400x240 Encoding:H.263 MP3)';
					break;
				case '34':
					$t = $title.'.flv (Size: 640x360 Encoding:AVC AAC)';
					break;
				case '35':
					$t = $title.'.flv (Size: 854x480 Encoding:AVC AAC)';
					break;
				case '18':
					$t = $title.'.mp4 (Size: 480x360 Encoding:AVC AAC)';
					break;
				case '22':
					$t = $title.'.mp4 (Size: 1280x720 Encoding:AVC AAC)';
					break;
				case '37':
					$t = $title.'.mp4 (Size: 1920x1080 Encoding:AVC AAC)';
					break;
				case '38':
					$t = $title.'.mp4 (Size: 4096x3072 Encoding:AVC AAC)';
					break;
				case '43':
					$t = $title.'.webm (Size: 854x480 Encoding:VP8 Vorbis)';
					break;
				case '45':
					$t = $title.'.webm (Size: 1280x720 Encoding:VP8 Vorbis)';
					break;
				case '17':
					$t = $title.'.3gp (Size: 176x144 Encoding:MPEG-4 Visual Vorbis)';
					break;
				default:
					$t = $title.' fmt='.$fmt;
					break;
			}

			$result[] = array(
				'title' => $t,
				'link' => $items[1]
			);
        } else {
            // default flash video link
            $result[] = array('title' => $title, 'link' => $link);
        }
    }

    if (0 == count($result)) {
        $result[] = array('title'=> 'Failed to retrieve YouTube link', 'link'=> NULL);
    }


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
function url_get_html($url, $data=NULL, $headers=NULL) {
	$obj = new Curl($url, $data, $headers);
	return $obj->get_content();
}


// execute main function
main();
?>


