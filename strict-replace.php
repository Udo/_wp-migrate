#!/usr/bin/php
<?
  $dir = getcwd();
  
  ob_start();
  ob_get_clean();
  
  include($dir.'/lib/udolib.php');

  $inFile = $argv[1];
  $outFile = $argv[2];
  $replIn = $argv[3];
  $replOut = $argv[4];
  
  function errorCancel($s)
  {
    die($s.chr(10).'Usage: ./strict-replace SOURCE DESTINATION SUBST_SOURCE SUBST_DESTINATION'.chr(10).
      'This tool puts each line of SOURCE into DESTINATION, replacing all the matching line items of SUBST_SOURCE with '.
      'the corresponding line items from SUBST_DESTINATION.'.chr(10));
  }
  
  if(!file_exists($inFile))
    errorCancel('Error: input file does not exist ('.$inFile.')');

  if(!file_exists($replIn))
    errorCancel('Error: substitution source file does not exist ('.$replIn.')');
    
  $substSource = array();
  foreach(explode(chr(10), file_get_contents($replIn)) as $line) if(trim($line) != '')
    $substSource[] = trim($line);

  if(!file_exists($replOut))
    errorCancel('Error: substitution destination file does not exist ('.$replOut.')');

  $substDestination = array();
  foreach(explode(chr(10), file_get_contents($replOut)) as $line) if(trim($line) != '')
    $substDestination[] = trim($line);

  if(sizeof($substDestination) != sizeof($substSource))
    errorCancel('Error: the number of lines in each substitution file must be equal (current: '.
      sizeof($substSource).'/'.sizeof($substDestination).')');
      
  @unlink($outFile);
  $fp = fopen($outFile, 'a');
      
  $lineNr = -1;
  
  print('replacing: '.implode(', ', $substSource).chr(10));
  print('with: '.implode(', ', $substDestination).chr(10));
    
  eachLine($inFile, function($line) use ($substSource, $substDestination, $fp, &$lineNr) {
    $lineNr += 1;
    $rLine = str_replace($substSource, $substDestination, $line);
    if($rLine != $line && stristr($line, $substSource[0]))
      print('match in line '.$lineNr.chr(10));
    fwrite($fp, $rLine);
  });
  
  fclose($fp);
  
  print(chr(10));