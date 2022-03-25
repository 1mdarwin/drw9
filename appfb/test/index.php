<?php
 
require "fb/src/facebook.php";


 
$fbconfig['appUrl'] = "https://apps.facebook.com/drwtesting"; 
$json_url = 'https://graph.facebook.com/drwsoluciones';
$app_url ='https://apps.facebook.com/drwtesting/';
$scope = 'email, publish_actions, read_friendlists';
 
// Create An instance of our Facebook Application.
$facebook = new Facebook(array(
  'appId'  => '426442104153800',
  'secret' => 'e2d640a391fa3e4c39e5d24da83b9fdf',
  'cookie' => true
));

 
// Get the app User ID
$user_id = $facebook->getUser();
$signed_request = $facebook->getSignedRequest();


?>
<html>
  <head></head>
  <body>

  <?php
    if($user_id) {
		// http://edcs.me/2013/05/like-gate-your-facebook-page-tab/
		// https://developers.facebook.com/docs/javascript/quickstart
		
      // We have a user ID, so probably a logged in user.
      // If not, we'll get an exception, which we handle below.
      try {		
        $user_profile = $facebook->api('/me','GET');
        $user_friends = $facebook->api('/me/friends','GET');
        $user_photos = $facebook->api('/me/photos','GET');

        //echo "Name: " . $user_profile['name']."</br>";
        $total_friends = count($user_friends['data']);
        //echo 'Total friends: '.$total_friends.'.<br />';
        $start = 0;
        while ($start < $total_friends) {
			//echo $user_friends['data'][$start]['id'] . '<br />';			
			//echo $user_friends['data'][$start]['name'] . '<br />';			
			$start++;
		}
        $json = file_get_contents($json_url);        
        $json_output = json_decode($json);
        $likes = 0;
        if ($json_output->likes){
			$likes = $json_output->likes;
		}
		//echo "Website: ". $json_output->link ."</br>";
		//echo $json_output->description ."</br>";
		if($signed_request['page']['id']){ 
			if($signed_request['page']['liked'])
				require('views/like.php');
			else
				require('views/no-like.php');
 
		}else{
			require('views/no-facebook.php');
		}
		
        

      } catch(FacebookApiException $e) {
        // If the user is logged out, you can have a 
        // user ID even though the access token is invalid.
        // In this case, we'll get an exception, so we'll
        // just ask the user to login again here.
        $login_url = $facebook->getLoginUrl(); 
        echo 'Please <a href="' . $login_url . '">login That.</a>';
        error_log($e->getType());
        error_log($e->getMessage());
      }   
    } else {

      // No user, print a link for the user to login
      $login_url = $facebook->getLoginUrl(array(
		'scope' => $scope,
		'redirect_uri'=> $app_url,
      ));      
      //echo 'Please <a href="' . $login_url . '">Login Here.</a>';
      print('<script> top.location.href=\'' . $login_url . '\'</script>');

    }

  ?>
  <span class="likesCount">Numero de likes: <?php echo $likes; ?></span>

  </body>
</html>
 
