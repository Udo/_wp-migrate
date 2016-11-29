<?php

# directory of table keys so we don't have to query them at runtime

class DB 
{

  function __construct()
  {
    $this->connect();
  }
  
  function isConnected()
  {
    return(is_resource($GLOBALS['db_link']));
  }

  function connect()
  {
    if($GLOBALS['db_link']) return;
    profiler_log('db:connect() start');
    $GLOBALS['db_link'] = mysqli_connect(cfg('db/host'), cfg('db/user'), cfg('db/password')) or
      critical('The database connection to server '.cfg('db/user').'@'.cfg('db/host').
        ' could not be established (code: '.@mysqli_connect_errno($GLOBALS['db_link']).')');
    if(cfg('db/database'))
      mysqli_select_db($GLOBALS['db_link'], cfg('db/database')) or
        critical('The database connection to database '.cfg('db/database').' on '.cfg('db/user').'@'.cfg('db/host').
          ' could not be established. (code: '.@mysqli_connect_errno($GLOBALS['db_link']).')');
    if(mysqli_character_set_name($GLOBALS['db_link']) != 'utf8') mysqli_set_charset($GLOBALS['db_link'], 'utf8');
    profiler_log('db:connect() done');
  }
    
  // get a list of datasets matching the $query
  function get($query, $parameters = null)
  {
    $result = array();
  
    $query = $this->parseQueryParams($query, $parameters);
    $this->lastQuery = $query;
  
    $lines = mysqli_query($GLOBALS['db_link'], $query) or critical(mysqli_error($GLOBALS['db_link']).' {query: '.$query.' }');
  
    while ($line = mysqli_fetch_array($lines, MYSQLI_ASSOC))
    {
      if (isset($keyByField))
        $result[$line[$keyByField]] = $line;
      else
        $result[] = $line;
    }
    mysqli_free_result($lines);

  	profiler_log('DB_GetList('.substr($query, 0, 40).'...)');
    return $result;
  }
  
  // gets a list of keys for the table
  function keys($otablename)
  {
    $tablename = $this->checkTableName($otablename);
    if(isset($GLOBALS['config']['dbinfo'][$otablename]))
      return($GLOBALS['config']['dbinfo'][$otablename]);
      
    $result = array();
    $sql = 'SHOW KEYS FROM `'.$tablename.'`';
    $res = mysqli_query($GLOBALS['db_link'], $sql) or critical(mysqli_error($GLOBALS['db_link']));
      
    while ($row = @mysqli_fetch_assoc($res))
    {
      if ($row['Key_name']=='PRIMARY')
        array_push($result, $row['Column_name']);
    }
    
    profiler_log('db::keys('.$tablename.') REBUILD KEY CACHE');
    $GLOBALS['config']['dbinfo'][$otablename] = $result;
    return($result);
  }
  
  // updates/creates the $dataset in the $tablename
  function commit($otablename, &$dataset)
  {
    $tablename = $this->checkTableName($otablename);
    $keynames = $this->keys($tablename);
    $keyname = $keynames[0]; 
    
    unset($GLOBALS['dbdatatmp'][$otablename][$keyname]);
  		 
    $query='REPLACE INTO '.$tablename.' ('.DB::MakeNamesList($dataset).
        ') VALUES('.DB::MakeValuesList($dataset).');';
        
    mysqli_query($GLOBALS['db_link'], $query) or critical(mysqli_error($GLOBALS['db_link']).'{ '.$query.' }');
    $dataset[$keyname] = first($dataset[$keyname], mysqli_insert_id($GLOBALS['db_link']));
    
    profiler_log('db::commit('.$tablename.', '.$dataset[$keyname].')');
    return $dataset[$keyname];
  }  
  
  function getDSMatch($table, $matchOptions, $fillIfEmpty = true, $noMatchOptions = array())
  {
    $where = array('1');
    if (!is_array($matchOptions))
      $matchOptions = stringParamsToArray($matchOptions);
    foreach($matchOptions as $k => $v)
      $where[] = '('.$k.'="'.$this->safe($v).'")';
    foreach($noMatchOptions as $k => $v)
      $where[] = '('.$k.'!="'.$this->safe($v).'")';
    $iwhere = implode(' AND ', $where);
  	$query = 'SELECT * FROM '.$this->checkTableName($table).
      ' WHERE '.$iwhere;
    $resultDS = $this->getDSWithQuery($query);
    if ($fillIfEmpty && sizeof($resultDS) == 0)
      foreach($matchOptions as $k => $v)
        $resultDS[$k] = $v;
    return($resultDS);
  }
  
  // from table $tablename, get dataset with key $keyvalue
  function getDS($tablename, $keyvalue, $keyname = '', $options = array())
  {
    if($keyvalue == '0') return(array());
    $fields = @$options['fields'];
    $fields = first($fields, '*'); 
    if (!$GLOBALS['db_link']) return(array());
  
    $this->checkTableName($tablename);
    if ($keyname == '')
    {
      $keynames = $this->keys($tablename);
      $keyname = $keynames[0];
    }
    
    $cache_entry = $tablename.':'.$keyname.':'.$keyvalue;
    
    if(isset($GLOBALS['dbdatatmp'][$cache_entry])) return($GLOBALS['dbdatatmp'][$cache_entry]);
  
    $query = 'SELECT '.$fields.' FROM '.$tablename.' '.$options['join'].' WHERE '.$keyname.'="'.DB::Safe($keyvalue).'";';
    $queryResult = mysqli_query($GLOBALS['db_link'], $query) or critical(mysqli_error($GLOBALS['db_link']).' { Query: "'.$query.'" }');
  
    if ($line = @mysqli_fetch_array($queryResult, MYSQLI_ASSOC))
    {
      mysqli_free_result($queryResult);
      $GLOBALS['dbdatatmp'][$cache_entry] = $line;
  	  profiler_log('DB_GetDataSet('.$tablename.', '.$keyvalue.')');
      return($line);    
    }
    else
      $result = array();
  
  	profiler_log('DB_GetDataSet('.$tablename.', '.$keyvalue.') #fail');
    return $result;
  }  

  function removeDS($tablename, $keyvalue, $keyname = null)
  {
    $this->checkTableName($tablename);
    if ($keyname == null)
    {
      $keynames = $this->keys($tablename);
      $keyname = $keynames[0];
    }
    return(mysqli_query($GLOBALS['db_link'], 'DELETE FROM '.$tablename.' WHERE '.$keyname.'="'.
      $this->safe($keyvalue).'";')
        or critical(mysqli_error($GLOBALS['db_link'])));
  }  

  // retrieve dataset identified by SQL $query
  function getDSWithQuery($query, $parameters = null)
  {
    $query = $this->parseQueryParams($query, $parameters);
  
    $queryResult = mysqli_query($GLOBALS['db_link'], $query);
    
    if(!$queryResult) 
      return(critical(mysqli_error($GLOBALS['db_link']).'{ '.$query.' }'));
  
  	if ($line = mysqli_fetch_array($queryResult, MYSQLI_ASSOC))
    {
      $result = $line;
      mysqli_free_result($queryResult);
    }
    else
      $result = array();

  	profiler_log('getDSWithQuery('.$query.')');
    return $result;
  }
  
  // execute a simple update $query
  function query($query, $parameters = null)
  {
    $query = $this->parseQueryParams($query, $parameters);
    if (substr($query, -1, 1) == ';')
      $query = substr($query, 0, -1);
    #WriteToFile('log/queries.'.date('Y-m').'.sql', '/* uid='.Account::uid().' */ '.$query.chr(10));
    return(mysqli_query($GLOBALS['db_link'], $query)
      or critical(mysqli_error($GLOBALS['db_link'])));
  }  

  // create a comma-separated list of keys in $dataset
  function makeNamesList(&$dataset)
  {
    $result = '';
    if (sizeof($dataset) > 0)
      foreach (array_keys($dataset) as $k)
      {
        if ($k!='')
          $result = $result.','.$k;
      }
    return substr($result, 1);
  }
  
  // make a name-value list for UPDATE-queries
  function makeValuesList(&$dataset)
  {
    $result = '';
    if (sizeof($dataset) > 0)
      foreach ($dataset as $k => $v)
      {
        if ($k!='')
          $result = $result.',"'.DB::safe($v).'"';
      }
    return substr($result,1);
  }  
    
  function parseQueryParams($query, $parameters = null)
  {
    if ($parameters != null)
    {
      $pctr = 0;
      $result = '';
      for($a = 0; $a < strlen($query); $a++)
      {
        $chr = substr($query, $a, 1);
        if ($chr == '?')
        {
          $result .= '"'.$this->safe($parameters[$pctr]).'"';
          $pctr++;
        }
        else if ($chr == '&')
        {
          $result .= ''.intval($parameters[$pctr]).'';
          $pctr++;
        }
        else if ($chr == ':')
        {
          $paramName = '';
          $a += 1;
          $pFormat = 'string';
          if($query[$a] == ':') 
          {
            $pFormat = 'number';
            $a += 1;
          }
          while(!ctype_space($chr = substr($query, $a, 1)) && $a < strlen($query))
          {
            $paramName .= $chr;
            $a += 1;
          }
          if($pFormat == 'number')
            $result .= ' '.($parameters[$paramName]+0).' ';
          else
            $result .= ' "'.$this->safe($parameters[$paramName]).'" ';
        }
        else
          $result .= $chr;
      }
    }
    else
      $result = $query;      
    return(str_replace('#', cfg('db/prefix'), $result));
  }
 
  function safe($raw)
  {
    if(!isset($GLOBALS['db_link']))
      return(addslashes($raw));
    else
      return(mysqli_real_escape_string($GLOBALS['db_link'], $raw));
  }

  function checkTableName(&$table, $makeSafe = true)
  {
  	$prefix = cfg('db/prefix');
    $len = strlen($prefix);
    if (substr($table, 0, $len) != $prefix)
      $table = $prefix.$table;
    if($makeSafe)
      $table = mysqli_real_escape_string($GLOBALS['db_link'], $table);
    return($table);
  }

}

function db() 
{
  if(!$GLOBALS['db-instance'])
    $GLOBALS['db-instance'] = new DB();
  return($GLOBALS['db-instance']);
}

$GLOBALS['dbobj'] = false;

?>