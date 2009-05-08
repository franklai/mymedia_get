<?php

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
echo '<pre>';
print_r($array);
echo '</pre>';
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
	}

	return $result;
}

/**
 * url handlers
 */
function url_handler_mymedia($url)
{
	$result = array();
	
echo 'url in mymedia handler, '.$url;
echo '<br/>';

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
echo 'pattern in get_link_by_url, '.$pattern;
echo '<br/>';
echo 'matches, '.print_r($matches);
echo '<br/>';

		if (2 != count($matches)) {
			return $link;
		}
		$id = $matches[1];
echo 'id in get_link_by_url, '.$id;
echo '<br/>';

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

		$pattern = '/"video_id": "([0-9a-zA-Z_\-]+)".*'
				   . '"t": "([0-9a-zA-Z_=]+)"/';
		preg_match($pattern, $html, $matches);
		if (3 != count($matches)) {
			return $links;
		}

		$youtube_link = sprintf('http://%s/get_video?video_id=%s&t=%s',
						 'www.youtube.com', $matches[1], $matches[2]);
		$h = url_get_html($youtube_link);

//		 $links['link'] = sprintf('http://%s/get_video?video_id=%s&t=%s',
//						  'www.youtube.com', $matches[1], $matches[2]);
//		 $links['hq_link'] = sprintf('http://%s/%s?video_id=%s&t=%s&fmt=18',
//						  'www.youtube.com', 'get_video',
//						  $matches[1], $matches[2]);
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
function url_get_html($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url); // set url
	curl_setopt($ch, CURLOPT_HEADER, 0); // do not output header
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, True); // not directly output
	// for Google App Engine test
// 	curl_setopt($ch, CURLOPT_USERAGENT, 'AppEngine-Google; (+http://code.google.com/appengine)');
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Mymedia Get');
// 	curl_setopt($ch, CURLOPT_REFERER, 'http://franklai-gb.appspot.com/');
// 	curl_setopt($ch, CURLOPT_REFERER, 'http://www.youtube.com/');
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

	$result = curl_exec($ch);
	curl_close($ch);

	return $result;
}

// execute main function
main();
?>
