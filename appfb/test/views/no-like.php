<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>You Don't Like My Page :(</title>
</head>
<body>

	<div id="fb-root"></div>
	<script>
		window.fbAsyncInit = function() {
			FB.init({
				appId      : '426442104153800',
				status     : true,
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
	<img src="http://www.drwsoluciones.net/appfb/test/images/page-tab-drw.jpg">	

</body>
</html>
