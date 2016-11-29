<?

$siteIdx = $_REQUEST['site']+0;
$site = $GLOBALS['config']['wp']['instances'][$siteIdx];

if(!$site)
  redirect('.?cmd=info');
  
?>

<h1>Exporting <?= htmlspecialchars($site['name']) ?></h1>

<?php
  
  $rand = alpha_encode(mt_rand(10240, 12800000));
  $date = gmdate('Y-m-d_Hi').$rand;
  $basef = str_replace('.', '-', basename($site['url']));
  
  shell_exec('nohup sh export-site.sh '.$basef.' '.
    escapeshellarg($basef.'.'.$date.'.site.tgz').' '.$site['path'].' 2>/dev/null >/dev/null &');
  
  redirect('?cmd=info');