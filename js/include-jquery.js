var $jQuery172;
var jQueryOld = null;

if(typeof(scriptLoaded172) == "undefined"){
	function scriptLoaded172(){
		$jQuery172 = jQuery.noConflict();
		$ = jQuery = jQueryOld;
		
		if(ISIjQueryLoaded) ISIjQueryLoaded();		
	}
}

if(typeof(loadISILib) == "undefined"){
	function loadISILib(link, callback){
		var headID = document.getElementsByTagName("head")[0];
		var newScript = document.createElement("script");
		newScript.type = "text/javascript";
		newScript.onload = callback;
		newScript.src = link;
		headID.appendChild(newScript);
	}
}

var options = {
	window: "",
	width: 500,
	bgColor: "#c0c0c0",
	waiting: 0,
	timer: 0,
	init: function(){},
	cl: function(){},
	rm: function(){},
	ok: function(){}
};
		
function showModal(data, options){
	// Background 
	$("#cl-"+options.window+"-background").css({
		position: "absolute",
		top: 0,
		left: 0,
		"background-color": "#000",
		opacity: 0.60,
		filter: "alpha(opacity=60)",
		width: window.innerWidth,
		height: window.innerHeight 
	});

	$("#cl-"+options.window+"-win").css({
		position: "absolute",
		top: 0,
		left: 0,
		"background-color": "transparent",
		opacity: 1,
		filter: "alpha(opacity=100)",
		width: window.innerWidth,
		height: window.innerHeight 
	});								
	// Container
	$("#cl-"+options.window+"-container").css({
		padding: 10,
		width: options.width,
		margin: "200px auto auto",
		"background-color": options.bgColor,
		opacity: 1,
		filter: "alpha(opacity=100)"								
	});								
	
	// Inititalisation
	options.init(window, options, data);
	
	// Display
	$("#cl-"+options.window+"-btn-cl").unbind("click");
	$("#cl-"+options.window+"-btn-cl").bind("click", function(evt){
		options.cl(evt, window, options, data);
		$("#cl-"+options.window+"-background").hide();
		$("#cl-"+options.window+"-win").hide();			
	});

	$("#cl-"+options.window+"-btn-rm").unbind("click");
	$("#cl-"+options.window+"-btn-rm").bind("click", function(evt){
		options.rm(evt, window, options, data);
	});					
	
	$("#cl-"+options.window+"-btn-ok").unbind("click");
	$("#cl-"+options.window+"-btn-ok").bind("click", function(evt){
		options.ok(evt, window, options, data);
	});							
	
	$("#cl-"+options.window+"-background").show();
	$("#cl-"+options.window+"-win").fadeIn();
}

$(function(){
	if(typeof($jQuery172) == "undefined") {
		$jQuery172 = null;
		jQueryOld = null;
		
		jQueryOld = jQuery.noConflict();
		var headID = document.getElementsByTagName("head")[0];
		var newScript = document.createElement("script");
		newScript.type = "text/javascript";
		newScript.onload = scriptLoaded172;
		newScript.src = "https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js";
		headID.appendChild(newScript);		
	}
});
		