<?php
  
  ob_get_clean();
  
  $params = $_REQUEST;
  
  $result = array(
    'result' => 'OK',
    'input' => $params,
    );
    
  if(!isset($params['sourceKey']))
    $result['error'] = 'Error: please choose a source site';

  if(!isset($params['destKey']))
    $result['error'] = 'Error: please choose a destination site';

  $instances = cfg('wp/instances');

  $srcInstance = $instances[$params['sourceKey']];
  if(!isset($srcInstance))
    $result['error'] = 'Error: source site not found (key #'.$params['sourceKey'].')';

  $destInstance = $instances[$params['destKey']];
  if(!isset($destInstance))
    $result['error'] = 'Error: destination site not found (key #'.$params['destKey'].')';

  if($destInstance == $srcInstance)
    $result['error'] = 'Error: you cannot copy a site onto itself';

  if(!isset($result['error']))
  {
    file_put_contents('privatedata/export-repl-in.txt', $params['substSrc']);
    file_put_contents('privatedata/export-repl-with.txt', $params['substDest']);
    
    $srcPath = $srcInstance['path'];
    $destPath = $destInstance['path'];
    $dbPass = cfg('db/password');
    $dbName = $srcInstance['db'];
    $destDB = $destInstance['db'];
    $xc = array();

    foreach(explode(chr(10), $params['exclude']) as $exLine)
    {
      $exLine = trim($exLine);
      if($exLine != '')
        $xc[] = '--exclude '.$exLine;
    }
    $exclude = escapeshellarg(implode(' ', $xc));

    WriteToFile('event.log', json_encode($result).chr(10));
    WriteToFile('event.log', gmdate('Y-m-d H:i:s').' MIGRATE user='.$_SESSION['migrationtool-uid'].
      ' from '.$srcPath.' to '.$destPath.chr(10));
    
    shell_exec('nohup sh export-migrate.sh '.$srcPath.' '.$destPath.' '.$dbPass.' '.$dbName.' '.
      $destDB.' '.$exclude.' 2>/dev/null >/dev/null &');
  }
    
  ob_get_clean();
  
  header('content-type: application/json');
  
  print(json_encode($result));
  
  file_put_contents('privatedata/export-last.json', json_encode($result));
  
  die();  