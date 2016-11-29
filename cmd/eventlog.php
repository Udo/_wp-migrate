<h1>Event log</h1>

<?php
  
  $logLines = explode(chr(10), trim(shell_exec('tail -n 100 event.log')));
  
  $logLines = array_reverse($logLines);
    
?>

<div>
<?php
  
  foreach($logLines as $line)
  {
    $style = '';
    if(substr($line, 0, 1) == '{')
    {
      $style = 'opacity:0.75;';
    }

    $line = str_replace(
      array('INFO', 'WARN', '{', '}', ','),
      array(
        '<span style="color:green">(INFO)</span>', 
        '<span style="color:red;font-weight:bold;">WARNING</span>',
        '<span style="color:green;">{</span>',
        '<span style="color:green;">}</span>',
        '<span style="color:blue;">,</span>',
        ),
      htmlspecialchars($line)
      );
    
    ?><div style="padding: 4px;<?= $style ?>"><?= ($line) ?></div><?
  }
  
?>
</div>