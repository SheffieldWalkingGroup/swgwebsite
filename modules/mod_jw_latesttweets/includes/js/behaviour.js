/**
 * @version		2.3
 * @package		Latest Tweets (module) for Joomla! 2.5 & 3.x
 * @author		JoomlaWorks - http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2015 JoomlaWorks Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

var jwLatestTweets = {
	ready: function(cb) {
		/in/.test(document.readyState) ? setTimeout('jwLatestTweets.ready(' + cb + ')', 9) : cb();
	},
	head: function() {
		return document.getElementsByTagName('head')[0];
	},
	lang: {
		lessthanaminute: "less than a minute ago",
		minute: "about a minute ago",
		minutes: "minutes ago",
		hour: "about an hour ago",
		hours: "hours ago",
		day: "1 day ago",
		days: "days ago"
	},
	getRelativeTime: function(time_value) {
	  var values = time_value.split(" ");
	  time_value = values[1] + " " + values[2] + ", " + values[5] + " " + values[3];
	  var parsed_date = Date.parse(time_value);
	  var relative_to = (arguments.length > 1) ? arguments[1] : new Date();
	  var delta = parseInt((relative_to.getTime() - parsed_date) / 1000);
	  delta = delta + (relative_to.getTimezoneOffset() * 60);
	  if (delta < 60) {
	    return this.lang.lessthanaminute;
	  } else if(delta < 120) {
	    return this.lang.minute;
	  } else if(delta < (60*60)) {
	    return (parseInt(delta / 60)).toString() + ' ' + this.lang.minutes;
	  } else if(delta < (120*60)) {
	    return this.lang.hour;
	  } else if(delta < (24*60*60)) {
	    return (parseInt(delta / 3600)).toString() + ' ' + this.lang.hours;
	  } else if(delta < (48*60*60)) {
	    return this.lang.day;
	  } else {
	    return (parseInt(delta / 86400)).toString() + ' ' + this.lang.days;
	  }
	},
	getTwitterJSON: function(url) {
		var s = document.createElement('script');
		s.setAttribute('charset', 'utf-8');
		s.setAttribute('type', 'text/javascript');
		s.setAttribute('async', 'true');
		s.setAttribute('src', url);
		return s;
	},
	fetchTweets: function(el) {
		var jwLtCallback = el.callback;
		var tempId = Math.floor(Math.random() * 1000) + 1;
		var responseContainer = [];
		window[jwLtCallback] = function(response) {
			responseContainer.tempId = [response];
		};
		var remoteScript = this.getTwitterJSON(document.location.protocol+'//json2jsonp.com/?callback='+el.callback+'&url=http%3A%2F%2Ftwitcher.steer.me%2Fuser_timeline%2F'+el.screen_name+'%3Fkey%3D'+el.key);
		var head = this.head();
		head.appendChild(remoteScript);
		remoteScript.onload = function() {
			var json = responseContainer.tempId[0];
			jwLatestTweets.twCB(json,el);
		}
	},
	twCB: function(tweets,el) {
	  var statusHTML = [];
	  var tweetsCount = el.count;
	  for(var i=0; i<tweetsCount; i++) {
	    var username = tweets[i].user.screen_name;
	    var avatar = tweets[i].user.profile_image_url;
	    var status = tweets[i].text.replace(/((https?|s?ftp|ssh)\:\/\/[^"\s\<\>]*[^.,;'">\:\s\<\>\)\]\!])/g, function(url) {
	      return '<a target="_blank" href="'+url+'">'+url+'</a>';
	    }).replace(/\B[@＠]([a-zA-Z0-9_]{1,20})/g, function(reply) {
	      return '<a target="_blank" href="https://twitter.com/'+reply.substring(1)+'">@'+reply.substring(1)+'</a>';
	    }).replace(/(^|\s+)#(\w+)/gi, ' <a target="_blank" href="https://twitter.com/search?q=%23$2">&#035;$2</a>');
	    if(status.substr(0,1)==' '){
	    	status = status.substring(1);
	    }
	    if(i%2==1) {
	    	var liClass = 'Even';
	    } else {
	    	var liClass = 'Odd';
	    }
	    var liOutput = '\
		    <li class="lt'+liClass+'">\
		    	<img class="ltUserAvatar" src="'+avatar+'" />\
			    <span class="ltUserStatus">'+status+'</span>\
			    <br />\
			    <a target="_blank" class="ltStatusTimestamp" href="https://twitter.com/'+username+'/status/'+tweets[i].id_str+'">\
			    	'+this.getRelativeTime(tweets[i].created_at)+'\
			    </a>\
		    </li>\
	    ';
	    statusHTML.push(liOutput);
	  }
	  setTimeout(function(){
	  	document.getElementById(el.moduleID).innerHTML = statusHTML.join('');
	  }, el.timeout);
	}
};
