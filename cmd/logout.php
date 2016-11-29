<h1>Sign out</h1>

<div class="banner">
  You have been signed out.
</div>

<?
  
  if($_SESSION['migrationtool-uid'])
    WriteToFile('event.log', gmdate('Y-m-d H:i:s').' INFO '.$_SESSION['migrationtool-uid'].' signed out from '.$_SERVER['IP'].chr(10));
    
  unset($_SESSION['migrationtool-uid']);
  