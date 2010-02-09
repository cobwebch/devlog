function toggleExtraData(theID) {
	var theLink = Ext.get('debug-link-' + theID);
	var theElement = Ext.get('debug-row-' + theID);
	if (theElement.visible()) {
		theElement.hide();
		theLink.update(devlog.imageExpand);
		theLink.title = devlog.show_extra_data;
	}
	else {
		theElement.show();
		theLink.update(devlog.imageCollapse);
		theLink.title = devlog.hide_extra_data;
	}
}

// JavaScript for menu switching
function jumpToUrl(URL)	{
	document.location = URL;
}

// JavaScript for automatic reloading of log window
var reloadTimer = null;

window.onload = function() {
  if(window.name=="devlog") {
	document.getElementById("openview").style.visibility = "hidden";
  }
  setReloadTime(devlog.autorefresh);
}

function setReloadTime(secs) {
  if (arguments.length == 1) {
	if (reloadTimer) clearTimeout(reloadTimer);
	if (secs) reloadTimer = setTimeout("setReloadTime()", Math.ceil(parseFloat(secs) * 1000));
  }
  else {
	//window.location.replace(window.location.href);
	document.options.submit();
  }
}

function toggleReload(autorefresh) {
	if(autorefresh){
		setReloadTime(2);
	}else{
		setReloadTime(0);
	}
}
