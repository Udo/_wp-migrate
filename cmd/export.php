<h1>Exporting <?= htmlspecialchars($_REQUEST['db']) ?></h1>

<?php
  
  $db = str_replace(array('"', "'", '\\'), '', basename(strip_tags($_REQUEST['db'])));
  $dbarg = escapeshellarg($db);
  
  $rand = alpha_encode(mt_rand(10240, 12800000));
  $date = gmdate('Y-m-d_Hi').$rand;
  
  shell_exec('nohup sh export-db.sh '.$dbarg.' '.escapeshellarg(cfg('db/password')).' '.
    ('"privatedata/'.$db.'.'.$date.'.sql.gz"').' 2>/dev/null >/dev/null &');

  redirect('?cmd=info');