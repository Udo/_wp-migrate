<h1>Sign in</h1>

<?
  if($_POST['user'])
  {
    $username = trim(strtolower($_POST['user']));
    $user = $GLOBALS['config']['users'][$username];
    if($user && $user['password'] == md5($_POST['password']))
    {
      ?><div class="banner">Login successful</div><?
      $_SESSION['migrationtool-uid'] = $username;
      WriteToFile('event.log', gmdate('Y-m-d H:i:s').' INFO '.$username.' signed in from '.$_SERVER['IP'].chr(10));
      redirect('?cmd=info');
    }
    else
    {
      ?><div class="banner">Access denied</div><?
      WriteToFile('event.log', gmdate('Y-m-d H:i:s').' WARN '.$username.' sign-in failure from '.$_SERVER['IP'].chr(10));
    }
  }
  
?>

<div>
  
  <form action="./" method="post">
    
    <input type="hidden" name="cmd" value="login"/>
   
    <div>
      <label class="field" for="user">Username: </label>
      <input type="text" name="user" id="user" placeholder="your username"/>
    </div>

    <div>
      <label class="field" for="password">Password: </label>
      <input type="password" id="password" name="password" placeholder="your password"/>
    </div>
    
    <div>
      <label class="field"></label>
      <input type="submit" value="Log in"/>
    </div>
    
  
  </form>
  
</div>