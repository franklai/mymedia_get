$(document).ready(function(){
	// initialize tab
	$("#tabs").tabs();

    /** binding */
	// select url when focus on #url [text input]
	$("#url, #sitesearch_text").bind("focus", function(e){
		if (jQuery.trim($(this).val()) == $(this).attr("title")){
			$(this).val("");
		}
		else {
			$(this).select();
		}
	});
	$("#url, #sitesearch_text").bind("blur", function(e){
		if (jQuery.trim($(this).val()) == ""){
			$(this).val($(this).attr("title"));
		}
	});

    // copy lyric
    $("#copy").bind("click", function(e){
        if (window.clipboardData){
            var text = $("#result_url > a").val();
            window.clipboardData.clearData(); 
            window.clipboardData.setData("Text", text); 
            window.alert("Lyric has copied to Clipboard.");
        }
        else {
            window.alert("Copy to Clipboard is IE only.");
        }
    });

    // binding examples URL
	$("#examples a").click(function(e){
        e.preventDefault();

        var url = e.target.href;
        $("#url").val(url);
        $("#btn_submit").submit();
    });

	// submit url to get episode list
	$("#query_form").bind("submit", function(e){
		// no form submit action
		e.preventDefault();

        var url = jQuery.trim($("#url").val());

		// check input
		if (url == "" || url == $("#url").attr("title")) {
			return false;
		}
		
		// show waiting dialog
		$("#btn_submit").attr("disabled", "disabled");
		$("#result_div").hide();
		$("#result_url").empty();
		$("#loading_div").html("Loading...");
		$("#loading_div").show();

		// JSON query
		$.getJSON(
			"app",
			{'url': url},
			function(links){
                var lines = new Array();

                for (var index in links) {
                    var link = links[index]["link"];
                    var title = links[index]["title"];

                    if (!link) {
                        link = "http://franks543.blogspot.com/2009/04/mymedia-get.html";
                        title = "Error occurred: Please contact franklai";
                    }

                    var link_html = '<a href="' + link 
                                    + '" target="_blank">' 
                                    + title + '</a>';
                    lines.push(link_html);
                }

                var links_html = lines.join("<br/>");

                $("#result_url").html(links_html);

                $("#loading_div").hide();
                $("#result_div").show();
                $("#btn_submit").removeAttr("disabled");
			}
		);
	});

    $("#loading_div").ajaxError(function(event, request, settings){
        $(this).html('<span style="color: red;">Error.</span>');
        $("#btn_submit").removeAttr("disabled");
    });
    
});
