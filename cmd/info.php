<?

function checkForFileOverflow(&$files)
{
  if(cfg('max-backup-files') == 0)
    return;
  while(sizeof($files) > cfg('max-backup-files'))
  {
    $fle = array_shift($files);
    @unlink('privatedata/'.$fle);
  }
}  
  
?>
<h1>WordPress Backup and Migration</h1>

<div class="section-header">Databases on this server</div>

<div id="db-list"><table>
  <thead>
    <th colspan="2">Database</th>
    <th>Status</th>
    <th># backups</th>
    <th>recent</th>
    <th>size</th>
  </thead><tbody><?php
  
  $dbList = db()->get('SHOW DATABASES');
  
  $excludeList = cfg('db/exclude-from-list');
  
  $backupFiles = array();
  foreach(file_list('privatedata/', '*sql.gz') as $bf)
  {
    $dbt = $bf;
    $dbf = nibble('.', $dbt);
    $backupFiles[$dbf][] = $bf;
  }
    
  foreach($dbList as $dbEntry) if(array_search($dbEntry['Database'], $excludeList) === false)
  {

    checkForFileOverflow($backupFiles[$dbEntry['Database']]);

    $status = trim(first(@file_get_contents('privatedata/status-'.$dbEntry['Database'].'-db'), 'done'));
    $size = sizeof($backupFiles[$dbEntry['Database']]);
    if($size == 0)
      $status = '-';
      
    $recentFile = $backupFiles[$dbEntry['Database']][$size-1];

    ?><tr>
      <td><button onclick="document.location.href='?cmd=export&db=<?= urlencode($dbEntry['Database']) ?>';">backup</button></td>
      <td><?= htmlspecialchars($dbEntry['Database']) ?></td>
      <td style="color:<?= $status == 'done' ? 'green' : 'red' ?>"><?= $status ?></td>
      <td><?= $size ?></td>
      <td><a href="privatedata/<?= $recentFile ?>"><?= $recentFile ?></a></td>
      <td><?= $recentFile ? 
        number_format(filesize('privatedata/'.$recentFile)/(1024*1024), 2).'MB' : '-' ?></td>
    </tr><?
  }
  
?></tbody></table></div>

<div class="section-header">WP instances & sites</div>

<div id="db-list"><table>
  <thead>
    <th>Site</th>
    <th></th>
    <th>Status</th>
    <th>Path</th>
    <th># backups</th>
    <th>recent</th>
    <th>size</th>
  </thead><tbody>
  
  <?php
  $backupFiles = array();
  foreach(file_list('privatedata/', '*site.tgz') as $bf)
  {
    $dbt = $bf;
    $dbf = nibble('.', $dbt);
    $backupFiles[$dbf][] = $bf;
  }
  
  foreach(cfg('wp/instances') as $ik => $instance)
  {
    $pth = explode('/', substr($instance['path'], -24));
    array_shift($pth);
    $path = implode('/', $pth);
    if(substr($path, 0, 1) == '/') $path = substr($path, 1);
    if(substr($path, -1) == '/') $path = substr($path, 0, -1);
    
    $basef = str_replace('.', '-', basename($instance['url']));    
    
    checkForFileOverflow($backupFiles[$basef]);

    $status = trim(first(@file_get_contents('privatedata/status-'.$basef), 'done'));
    $size = sizeof($backupFiles[$basef]);
    if($status == 'done' && $size == 0)
      $status = '-';
      
    $recentFile = $backupFiles[$basef][$size-1];
    
    ?><tr>
      <td><button onclick="document.location.href='?cmd=export-site&site=<?= $ik ?>';">backup</button></td>
      <td><?= $instance['name'] ?></td>
      <td style="color:<?= $status == 'done' ? 'green' : 'red' ?>"><?= $status ?></td>
      <td style="font-size:80%;"><b><?= $instance['url'] ?></b><br/><?= $path ?></td>
      <td><?= $size ?></td>
      <td><a href="privatedata/<?= $recentFile ?>"><?= $recentFile ?></a></td>
      <td><?= $recentFile ? 
        number_format(filesize('privatedata/'.$recentFile)/(1024*1024), 2).'MB' : '-' ?></td>
    </tr><?
  }
  ?>
  
</tbody></table></div>

<div class="section-header">Status</div>

<div id="db-list">
<?
  $lines = explode(chr(10), shell_exec('df'));
  foreach($lines as $line)
  {
    $cmd = explode(' ', preg_replace('/\s+/', ' ', $line));
    if($cmd[5] == '/')
    {
      $total = $cmd[3]+0;
      $used = $cmd[2]+0;
      $usedPx = 200*($used/$total);
      $freePx = 200*(1-($used/$total));
      ?><div>
        <div style="padding:3px;display:inline-block;vertical-align: middle;">Disk Space:</div> 
        <div style="font-size:80%;padding-top:6px;text-align:center;color:white;height:24px;display:inline-block;width:<?= $usedPx ?>px;background:blue;vertical-align: middle;">
          <?= round($used/(1024*1024)) ?>GB
        </div><div 
          style="font-size:80%;padding-top:6px;text-align:center;color:gray;height:24px;display:inline-block;width:<?= $freePx ?>px;background:rgba(0,0,0,0.2);vertical-align: middle;">
            <?= round(($total-$used)/(1024*1024)) ?>GB
          </div>
        <div style="padding:3px;display:inline-block;vertical-align: middle;"><?= round(100*(1-($used/$total))) ?>% free</div>
      </div><?
    }
  }
?>
</div>

<div id="db-list">
  
  <div style="font-size:130%;"><b>Last migration: </b></div>
  <div style="margin:16px;">
  
  <?
  $lm = json_decode(file_get_contents('privatedata/export-last.json'), true);
  if(sizeof($lm) > 0)
  {
    $t = filemtime('privatedata/export-last.json');
    $params = $lm['input'];
    $instances = cfg('wp/instances');
    $srcInstance = $instances[$params['sourceKey']];
    $destInstance = $instances[$params['destKey']];
    print('from <b>'.$srcInstance['url'].'</b> <br/>to <b>'.$destInstance['url'].'</b> <br/>
      '.ageToString($t).'<br/>
      status: <b>'.file_get_contents(
      'privatedata/status-export'
      ).'</b>');
  }
  else
  {
    print('(none)');
  }
  ?>
  </div>
  
</div>


<script>
  
  setTimeout(function() {
    document.location.reload();
    }, 5000);
  
</script>