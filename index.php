<html lang="zh-tw">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>yam mymedia Retriever</title>
<style type="text/css">
input#url {
width: 500px;
}
#dl_link {
width: 700px;
height: 20px;
border: 1px solid #CCC;
background: #EEE;
padding: 6px;
}
#ta1 {
width: 800px;
height: 300px;
padding: 3px;
white-space: nowrap;
}
</style>

<script src="ajax.js"></script>

<script>
function $(name) {
    return document.getElementById(name);
}

String.prototype.trim = function () {
    return this.replace(/^\s+|\s+$/g, "");
}

/**Ajax Request (Submits the form below through AJAX
 *               and then calls the ajax_response function)
 */
function ajax_request() {
    var submitTo = 'request.php';
    //location.href = submitTo; //uncomment if you need for debugging
    var method = 'POST';
    //var method = 'GET';
    var query = $("url").value;
    if ( check_input(query) == false) {
        return false;
    }

    // display message
    $("ta1").value = "retrieving url...please wait.";

    http(method, submitTo, ajax_response, 'url='+query);
}

/**Ajax Response (Called when ajax data has been retrieved)
 *
 * @param   object  data   Javascript (JSON) data object received
 *                         through ajax call
 */
function ajax_response(data) {
    //var lyric = data['lyric'];
    //alert(listMember(data, "\n"));
    if (!data){
        $("ta1").value = "invalid input.";
        $("url").select();
        return false;
    }
    $("ta1").value = data.link;

    // clear text
    $("dl_link").innerHTML = "";

    var obj = document.createElement("a");
    obj.appendChild(document.createTextNode(data.title));
    obj.setAttribute("href", data.link);
    $("dl_link").appendChild(obj);

    $("url").select();
}

function listMember(obj, tail)
{
    var str = "";

    for (var item in obj){
        str += item + ":" + obj[item] + tail;
    }
    
    return str;
}

function keypressHandler(e)
{
    e = (!e)? window.event: e;
    if (e.keyCode == 13){ // 13 means Enter (Carriage Return)
        $("button1").click();
        return false;
    }
}

function check_input(value)
{
    if ( value.trim() == "") {
        return false;
    }
    
    return true;
}


window.onload = init;

function init()
{
    var button = $("button1");
    button.onclick = ajax_request;

    var urlObj = $("url");
    urlObj.onkeypress = keypressHandler;
    urlObj.focus();
}

</script>


</head>
<body>
<ul>
<li><a href="http://mymedia.yam.com/" target="_blank">yam mymedia</a>
<div>http://mymedia.yam.com/m/1813911</div>
</li>
</ul>

<form id="form1" name="form1">
<label for="url">URL</label> 
<input type="text" id="url" name="url" value="">
<input type="button" id="button1" value="query">

<div id="dl_link">Link shows here</div>

<textarea id="ta1">Link shows here</textarea>
</form>


</body>
<script src='http://www.google-analytics.com/ga.js' type='text/javascript'></script>
<script type='text/javascript'>
var pageTracker = _gat._getTracker("UA-879036-1");
pageTracker._initData();
pageTracker._trackPageview();
</script>
</html>

