<?php
  
# **************************** GENERAL UTILITY FUNCTIONS ******************************

error_reporting(E_ALL & ~E_NOTICE);

if(!$GLOBALS['profiler_start'])
  $GLOBALS['profiler_start'] = microtime(true);

# if $def1 is empty, use $def2
function first()
{
  $args = func_get_args();
  foreach($args as $v)
  {
    if(isset($v) && $v !== false && $v !== '' && $v !== null)
      return($v);
  }
}

# append any string to the given file
function writeToFile($filename, $content)
{
  if (is_array($content)) $content = getArrayDump($content);
  $open = fopen($filename, 'a+');
  fwrite($open, $content);
  fclose($open);
  @chmod($filename, 0777);
}

function file_list($path, $pattern = false) 
{
  $items = array();
  
  foreach(scandir($path, SCANDIR_SORT_ASCENDING) as $item)
  {
    if(substr($item, 0, 1) != '.' && ($pattern === false || fnmatch($pattern, $item)))
      $items[] = $item;
  }
  
  return($items);
}

function eachLine($file, $f)
{
  $handle = fopen($file, "r");
  if ($handle) {
      while (($line = fgets($handle)) !== false) {
          $f($line);
      }  
      fclose($handle);
  } else {

  } 
}

# **************************** ARRAY FUNCTIONS ******************************

function map($list, $func)
{
  $result = array();
  if($list !== null && $list !== false)
  {
    if(!is_array($list)) $list = array($list);
    foreach($list as $key => $item)
    {
      $v = $func($item, $key);
      if($v !== null)
        $result[] = $v;
    }
  }
  return($result);
}

function reduce($list, $func)
{
  $result = null;
  if($list !== null && $list !== false)
  {
    if(!is_array($list)) $list = array($list);
    foreach($list as $key => $item)
    {
      $v = $func($result, $item, $key);
      if($v !== null)
        $result = $v;
    }
  }
  return($result);
}

# **************************** STRING/FORMATTING FUNCTIONS ******************************

function alpha_encode($int, $alphabet = "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ") {
	$base58_string = "";
	$base = strlen($alphabet);
	while($int >= $base) {
		$div = floor($int / $base);
		$mod = ($int - ($base * $div)); // php's % is broke with >32bit int on 32bit proc
		$base58_string = $alphabet{$mod} . $base58_string;
		$int = $div;
	}
	if($int) $base58_string = $alphabet{$int} . $base58_string;
	return $base58_string;
}

function alpha_decode($base58, $alphabet = "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ") {
	$int_val = 0;
	for($i=strlen($base58)-1,$j=1,$base=strlen($alphabet);$i>=0;$i--,$j*=$base) {
		$int_val += $j * strpos($alphabet, $base58{$i});
	}
	return $int_val;
}

function ageToString($unixDate, $new = 'just now', $ago = 'ago')
{
  if($unixDate == 0) return('-');
  $result = '';
  $oneMinute = 60;
  $oneHour = $oneMinute*60;
  $oneDay = $oneHour*24;
  
  $difference = time() - $unixDate;
  
  if ($difference < $oneMinute)
    $result = $new;
  else if ($difference < $oneHour)
    $result = round($difference/$oneMinute).' min '.$ago;
  else if ($difference < $oneDay)
    $result = floor($difference/$oneHour).' h '.$ago;
  else if ($difference < $oneDay*5)
    $result = gmdate('D H:i', $unixDate);
  else if ($difference < $oneDay*365)
    $result = gmdate('M dS H:i', $unixDate);
  else
    $result = date('d. M Y H:i', $unixDate);
  return($result);
}

function capitalize($name)
{
  return(strtoupper(substr($name, 0, 1)).substr($name, 1));
}

function nibble($segdiv, &$cake, &$found = false)
{
  $p = strpos($cake, $segdiv);
  if ($p === false)
  {
    $result = $cake;
    $cake = '';
    $found = false;
  }
  else
  {
    $result = substr($cake, 0, $p);
    $cake = substr($cake, $p + strlen($segdiv));
    $found = true;
  }
  return $result;
}

function startsWith($s, $match)
{
  return(substr($s, 0, strlen($match)) == $match);
}

function endsWith($s, $match)
{
  return(substr($s, -strlen($match)) == $match);
}

function truncate($s, $maxLength, $indicator = '')
{
  if(strlen($s) <= $maxLength) 
    return($s);
  else
    return(substr($s, 0, $maxLength).$indicator);
}

function match($subject, $criteria)
{
  $result = true;
  foreach($criteria as $k => $v)
  {
    if($subject[$k] != $v) $result = false;
  }
  return($result);
}

function parseRequestURI($uri = false)
{ 	
  $result = parse_url(@first($uri, $_SERVER['REQUEST_URI']));

  if(isset($result['query']))
  {
    if(strpos($result['query'], '?') !== false || strpos($result['query'], '&') === false)
    {
      $result['path2'] = nibble('?', $result['query']);
    }
    parse_str($result['query'], $http_query);
    $_SERVER['QUERY_STRING'] = $result['query'];
    if(is_array($http_query)) 
      foreach($http_query as $k => $v) $_REQUEST[$k] = $v;
    $result['query'] = $http_query;
  }

  foreach(array('path', 'path2') as $p)  
	  while(substr($result[$p], 0, 1) == '/' || substr($result[$p], 0, 1) == '.')
	    $result[$p] = substr($result[$p], 1);

  return($result);
}

function element($name)
{
  $args = func_get_args();
  $name = array_shift($args);
  if(!isset($GLOBALS['elementCache'][$name]))
  {
    if(isset($GLOBALS['elementLocator']))
      $GLOBALS['elementCache'][$name] = $GLOBALS['elementLoader']($name);
    else
      $GLOBALS['elementCache'][$name] = require(@first($GLOBALS['elementDir'], '').$name.'.php');
  }
  return(call_user_func_array($GLOBALS['elementCache'][$name], $args));
}

function redirect($url)
{
  header('location: '.$url);
  die();
}

function cfg($name, $default = null)
{
	$vr = &$GLOBALS['config'];
	foreach(explode('/', $name) as $ni) 
	  if(is_array($vr)) $vr = &$vr[$ni]; else $vr = '';
  if($default == null && is_array($vr))
    return($vr);
	return(first($vr, $default));
}

function profiler_getlog()
{
  return($GLOBALS['profiler_log']);
}

# makes a commented profiler entry 
function profiler_log($text, $backtrace = false)
{
  $thistime = microtime(true);
  $absoluteMS = ($thistime - $GLOBALS['profiler_start']);
  $GLOBALS['profiler_log'][] = $absoluteMS.' | '.($thistime - $GLOBALS['profiler_last']).' msec | '.
    ceil(memory_get_usage()/1024).' kB | '.$text;
  if(cfg('profiler-backtrace') || $backtrace)
  {
    if(!$backtrace)
    {
      $backtrace = debug_backtrace();
      if(sizeof($backtrace) > 1) array_shift($backtrace);
    }
    $GLOBALS['profiler_log'][] = json_encode($backtrace).chr(10);
  }
  $GLOBALS['profiler_last'] = $thistime;
  $GLOBALS['profiler_current'] = $absoluteMS;
}






