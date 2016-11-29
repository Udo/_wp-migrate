<h1>Migrate Database</h1>

<form><div style="display: flex;" class="inset">
  
  <div style="flex:1">
    
    <div class="section-header">Source Site</div>
    
    <div id="db-list" style="padding:12px;"><table>
      <thead>
        <th>Site</th>
        <th></th>
        <th>Path</th>
      </thead><tbody>
      
      <?php
      
      foreach(cfg('wp/instances') as $ik => $instance) if($instance['type'] == 'wp')
      {
        $pth = explode('/', substr($instance['path'], -24));
        array_shift($pth);
        $path = implode('/', $pth);
        if(substr($path, 0, 1) == '/') $path = substr($path, 1);
        if(substr($path, -1) == '/') $path = substr($path, 0, -1);
        
        $basef = str_replace('.', '-', basename($instance['url']));    
        
        $status = trim(first(@file_get_contents('privatedata/status-'.$basef), 'done'));
        $size = sizeof($backupFiles[$basef]);
        if($status == 'done' && $size == 0)
          $status = '-';
          
        $recentFile = $backupFiles[$basef][$size-1];
        
        $cs = md5('src-'.$ik);
        
        ?><tr class="src-row" id="row-<?= $cs ?>">
          <td>&nbsp;<input type="radio" id="r-<?= $cs ?>" 
              onclick="selectSource(<?= $ik ?>, '<?= $cs ?>');"
              name="select-source"/></td>
          <td><?= $instance['name'] ?></td>
          <td style="font-size:80%;"><b><?= $instance['url'] ?></b><br/><?= $path ?></td>
        </tr><?
      }
      ?>
      
    </tbody></table></div>

    
  </div>

  <div style="flex:0.1">
    <div style="font-size: 96px;margin-top:-50px;">➤</div>
  </div>

  <div style="flex:1">
    
    <div class="section-header">Destination Site</div>
    
    <div id="db-list" style="padding:12px;"><table>
      <thead>
        <th>Site</th>
        <th></th>
        <th>Path</th>
      </thead><tbody>
      
      <?php
      
      foreach(cfg('wp/instances') as $ik => $instance) if($instance['type'] == 'wp')
      {
        $pth = explode('/', substr($instance['path'], -24));
        array_shift($pth);
        $path = implode('/', $pth);
        if(substr($path, 0, 1) == '/') $path = substr($path, 1);
        if(substr($path, -1) == '/') $path = substr($path, 0, -1);
        
        $basef = str_replace('.', '-', basename($instance['url']));    
        
        $status = trim(first(@file_get_contents('privatedata/status-'.$basef), 'done'));
        $size = sizeof($backupFiles[$basef]);
        if($status == 'done' && $size == 0)
          $status = '-';
          
        $recentFile = $backupFiles[$basef][$size-1];
        
        $cs = md5('dest-'.$ik);
        
        ?><tr class="dest-row" id="row-<?= $cs ?>">
          <td>&nbsp;<input type="radio" id="r-<?= $cs ?>" 
              onclick="selectDestination(<?= $ik ?>, '<?= $cs ?>');"
              name="select-destination"/></td>
          <td><?= $instance['name'] ?></td>
          <td style="font-size:80%;"><b><?= $instance['url'] ?></b><br/><?= $path ?></td>
        </tr><?
      }
      ?>
      
    </tbody></table></div></div>
  
</div></form>

<div class="section-header">Substitutions</div>

<?php
  
  $substSrc = '';
  $substDest = '';
  
?>

<div style="display:flex;">
  
  <div style="flex:1">
    <textarea onchange="saveSubst('substSrc', $(this).val());" id="subst-src"><?= $substSrc ?></textarea><br/>
    <div style="opacity:0.5">&nbsp; one substitution per line</div>
  </div>
  
  <div style="flex:0.1">
    <div style="font-size: 96px;margin-top:-50px;">=</div>
  </div>

  <div style="flex:1">
    <textarea onchange="saveSubst('substDest', $(this).val());" id="subst-dest"><?= $substDest ?></textarea>
  </div>
  
</div>

<div style="display:flex;" class="inset">
  
  <div style="flex:1">
    <div class="section-header">Exclude Files</div>
    <div style="padding:12px;">
      <textarea onchange="saveSubst('excludeFiles', $(this).val());" id="exclude"><?= $seclude ?></textarea><br/>
      <div style="opacity:0.5">&nbsp; one file per line</div> 
    </div>
  </div>
  
  <div style="flex:0.1">

  </div>

  <div style="flex:1">
    <div class="section-header">Action!</div>
    <div style="padding:12px;">
      <div class="error-banner"></div>
      <button
        style="padding:12px;"
        onclick="if(confirm('Are you sure you want to overwrite the destination site?')) executeMigration();">Execute Migration &gt;</button>
    </div>
  </div>
  
</div>



<script>
  
var mParams = {};

var instances = <?= json_encode(cfg('wp/instances')) ?>;
  
var selectSource = function(sourceKey, idBase) {
  $('.src-row').removeClass('selected-row');
  $('#row-'+idBase).addClass('selected-row');
  mParams.sourceKey = sourceKey;
  $('#subst-src').val(instances[sourceKey].url);
  mParams.substSrc = instances[sourceKey].url;
  $('#exclude').val(instances[sourceKey].exclude);
  mParams.exclude = instances[sourceKey].exclude;
}

var selectDestination = function(destKey, idBase) {
  $('.dest-row').removeClass('selected-row');
  $('#row-'+idBase).addClass('selected-row');
  mParams.destKey = destKey;
  $('#subst-dest').val(instances[destKey].url);
  mParams.substDest = instances[destKey].url;
}

var saveSubst = function(field, content) {
  mParams[field] = content;
}

var executeMigration = function() {
  mParams.cmd = 'migrate-execute';
  mParams.exclude = $('#exclude').val();
  mParams.substSrc = $('#subst-src').val();
  mParams.substDest = $('#subst-dest').val();
  $.post('?', mParams, function(data) {
    console.log(data);
    if(!data.error) {
      document.location.href = '?cmd=info';
    } else {
      $('.error-banner').html(data.error);
    }
  }, 'json');
}

  
</script>







