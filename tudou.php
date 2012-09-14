<?php
require_once('curl.php');
require_once('common.php');

/**
 *1.http://www.tudou.com/programs/view/Veq0WIbqwa8/
 *    find iid from html
 *2.http://v2.tudou.com/v.action?st=2,3,4,5,99&it={iid}
 *    the response is xml, containing variable video links (different resolution)
 *3.http://183.61.72.7/f4v/92/144127992.h264_98.f4v?key=89a89d0ae79641105d3826503463f60040488db399&tk=155012700719874303900509033&brt=99&nt=0&du=1205070&ispid=3&rc=207&inf=1&si=un&npc=3800&pp=0&ul=0&mt=-1&sid=0&rid=0&rst=0&au=0&id=tudou&itemid=100627895
 *    real video link
 *
 * Note: the 2nd step and 3rd step need to be the same User-Agent
 *       a solution is send step 2 User-Agent as browser user agent, using $_SERVER['HTTP_USER_AGENT']
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
        $html = mb_convert_encoding($html, "UTF-8", "GBK");

        $iid = $this->getIid($html, $url);
        if (FALSE === $iid) {
            return FALSE;
        }

        $videoListUrl = sprintf("http://v2.tudou.com/v.action?st=2,3,4,5,99&it=%s", $iid);
        Common::debug("url is $videoListUrl");

        $response = new Curl(
            $videoListUrl,
            NULL,
            array('User-Agent' => $_SERVER['HTTP_USER_AGENT'])
        );
        $xml = $response->get_content();

        $videoList = $this->parseXml($xml, $html, $url);
    }

    private function getIid($html, $url) {
        // /programs/view/[icode]
        $pattern = '/iid = ([0-9]+)/';
        $iid = Common::getFirstMatch($html, $pattern);

        if (empty($iid) && Common::hasString($url, 'play/')) {
            $icode = $this->getIcode($html, $url);
            if (empty($icode)) {
                return FALSE;
            }

            $iid = $this->getIidByIcode($html, $icode);
        }

        return $iid;
    }

    private function getIcode($html, $url) {
        // /albumplay/[acode]/[icode]
        // /listplay/[lcode]/[icode]
        $pattern = '/play\/[^\/]+\/([^\/]+).html/';
        $icode = Common::getFirstMatch($url, $pattern);

        if (empty($icode)) {
            $icode = Common::getSubString($html, "location.href) || '", "'");
        }

        return $icode;
    }

    private function getIidByIcode($html, $icode) {
        $iidPattern = '/\niid:([0-9]+)/';
        $icodePattern = '/\n,icode:"([^"]+)"/';

        $iidAll = Common::getAllFirstMatch($html, $iidPattern);
        $icodeAll = Common::getAllFirstMatch($html, $icodePattern);

        if (empty($iidAll) || empty($icodeAll)) {
            return FALSE;
        }

        if (count($iidAll) === 0 || count($icodeAll) === 0) {
            return FALSE;
        }
        $key = array_search($icode, $icodeAll);
        if ($key === FALSE) {
            return FALSE;
        }

        return $iidAll[$key];
    }

    private function parseXml($xml, $html, $url) {
        $list = array();

        if (strpos($xml, "error='ip is forbidden'") !== FALSE) {
            $this->result[] = array(
                "title" => "IP is forbidden",
                "link" => $url
            );
            return $list;
        }

        $decodedXml = Common::decodeHtml($xml);

        $title = $this->parseTitle($decodedXml, $html, $url);

// echo $decodedXml;
//         echo str_replace(">", ">\n", $decodedXml);

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

    private function parseTitle($xml, $html, $url) {
        $defaultTitle = 'Unknown title';

        $title = $this->parseTitleFromXml($xml);
        if (empty($title)) {
            $title = $this->parseTitleFromHtml($html);
        }

        if (empty($this)) {
            $title = $defaultTitle;
        }

        return $title;
    }

    private function parseTitleFromXml($xml) {
        $pattern = '/title="([^"]+)"/';
        $ret = Common::getFirstMatch($xml, $pattern);
        if (FALSE === $ret) {
            return FALSE;
        }

        $title = $ret;

        return $title;
    }

    private function parseTitleFromHtml($html) {
        $pattern = '/<h1>([^<]+)<\/h1>/';
        return Common::getFirstMatch($html, $pattern);
    }

    private function getConvertedTitle($title, $brt) {
        $ret = $title . $this->getFormatString($brt);

        return $ret;
    }

    private function getFormatString($brt) {
        if (array_key_exists($brt, $this->brtList)) {
            return sprintf(" (%s)", $this->brtList[$brt]);
        } else {
            return sprintf(" (Unknown brt %s)", $brt);
        }
    }

    public function get_title() {
        return $this->title;
    }

    public function get_links() {
        return $this->links;
    }

    public function get_result() {
        return $this->result;
    }
}

// php -d open_basedir= tudou.php
if (!empty($argv) && basename($argv[0]) === basename(__FILE__)) {
//     $url = 'http://www.tudou.com/programs/view/YwGhpN2C_KI/';
//     $url = 'http://www.tudou.com/programs/view/ogyN7FvbENM/?fr=rec1';
//     $url = 'http://www.tudou.com/listplay/GbbmprNY16w/WnMfkxSleL4.html';
//     $url = 'http://www.tudou.com/programs/view/UHcWm65Nt-c/';
//     $url = 'http://www.tudou.com/programs/view/7dWtoMMsaYE/';
//     $url = 'http://www.tudou.com/programs/view/Xohot2RSQJw/';
//     $url = 'http://www.tudou.com/programs/view/z5VaKOkifzQ/';

//     // ip forbidden
//     $url = 'http://www.tudou.com/programs/view/rJpTeDQJvEs/';
//     $url = 'http://www.tudou.com/albumplay/n9e8zZsySQc/bmT51zM7_3o.html';

//     $url = 'http://www.tudou.com/listplay/avYiZY4TUxA/H6yyw65w7Io.html';
//     $url = 'http://www.tudou.com/programs/view/9oi-HEJGKxI';
    $url = 'http://www.tudou.com/listplay/iufZIeLCFFo/s4ava8gU7k0.html';

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

// vim: expandtab
?>
