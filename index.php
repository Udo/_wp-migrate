<?php
  
  include('config.php');
  include('lib/udolib.php');
  include('lib/db.php');
  
  $_SERVER['IP'] = first($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);
  
  session_start();
  
  ob_start();
  
  if(!$_SESSION['migrationtool-uid'])
    $_REQUEST['cmd'] = 'login';
    
  $cmdFile = 'cmd/'.first($_REQUEST['cmd'], 'info').'.php';
  
?><!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>WP Migration Tool</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="favicon.png">
        <link rel="icon" href="favicon.png" />

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/main.css">
        <link rel="stylesheet" href="css/site.css?v=1">
        
        <script src="https://code.jquery.com/jquery-1.12.0.min.js"></script>
    </head>
    <body>
      <nav>
      <a href="?cmd=info">Dashboard</a> 
      <?
      if($_SESSION['migrationtool-uid'])
      {
        ?>
        <a href="?cmd=migrate">Migrate</a> 
        <a href="?cmd=eventlog">Events</a> 
        <a href="?cmd=logout">Sign out</a> 
        <span><?= $_SESSION['migrationtool-uid'] ?></span>
        <?
      }
      else
      {
        ?><span>( not signed in )</span><?
      }
      ?>
      </nav>
      <?  
      if(file_exists($cmdFile))
        include($cmdFile);
      else
        print('command not found: '.htmlspecialchars($_REQUEST['cmd']));
      ?>
    </body>
</html>
<?
  
  print(ob_get_clean());