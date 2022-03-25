<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>You Like My Page!</title>
</head>
<body>

	<div id="fb-root"></div>
	<script>
		window.fbAsyncInit = function() {
			FB.init({
				appId      : '426442104153800',                        // App ID from the app dashboard
				//channelUrl : '//WWW.YOUR_DOMAIN.COM/channel.html', // Channel file for x-domain comms
				status     : true,                                 // Check Facebook Login status
				xfbml      : true  
			});
				
			FB.Event.subscribe('edge.create', function(response) {
				parent.location.reload();
			});
		};
			
		(function(d, s, id){
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/en_US/all.js";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
	</script>


	<h1>You Like My Page!</h1>
	<p>You could win a DRW Solution's t-shirt if you choose the better product </p>

</body>
</html>
