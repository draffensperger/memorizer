<?php
$url = parse_url(getenv("CLEARDB_DATABASE_URL"));

$mysql_server = $url["host"];
$mysql_user = $url["user"];
$mysql_password = $url["pass"];
$mysql_database = substr($url["path"], 1);

// The database connection
$conn = '';

function openDBConn() {
  global $mysql_server;
  global $mysql_user;
  global $mysql_password;    
  global $mysql_database;      

  if(usingAppFog()) {
    $credentials = getAppFogServicesCredentials('mysql-5.1', 'mysql');
    $mysql_server = $credentials['host'].':'.$credentials['port'];
    $mysql_user = $credentials['user'];
    $mysql_password = $credentials['password'];
    $mysql_database = $credentials['name'];
  }  

  global $conn;
  $conn = mysql_connect($mysql_server, $mysql_user, $mysql_password);
  if (!$conn) {
    die('Could not connect: ' . mysql_error());
  }

  if (!mysql_select_db($mysql_database, $conn)) {
    die('Could not set database: ' . mysql_error());    
  }
}

function usingAppFog() {
  return getenv('VCAP_SERVICES') != false;
}

function getAppFogServicesCredentials($service, $name) {
  $vcap_services = json_decode(getenv("VCAP_SERVICES"), true);	

  $servers = $vcap_services[$service];	

  foreach ($servers as $server) {
    if ($server['name'] == $name) {
      return $server['credentials'];
    }
  }

  return null;
}

function getLastInsertID() {
  global $conn;
  return mysql_insert_id($conn);
}

function getSQLScalar($sql) {
  global $conn;

  $result = mysql_query($sql, $conn) or die("Error in query: $sql." . mysql_error($conn));
  $row = mysql_fetch_row($result);  

  return $row[0];
}


function getSQLRows($sql) {
  global $conn;

  $result = mysql_query($sql, $conn) or die("Error in query: $sql." . mysql_error($conn));
  return mysql_fetch_all($result);  
}

// Returns a single row as an array that can be referenced as array["<field name>"] for the 
// first row of the query.
function getSQLRow($sql) {
  global $conn;

  $result = mysql_query($sql, $conn) or die("Error in query: $sql." . mysql_error($conn));
  return mysql_fetch_assoc($result);
}

function getSQLMap($sql, $keyField, $valueField) {
  $rows = getSQLRows($sql);
  $map = array();

  foreach ($rows as $row) {
    $map[$row[$keyField]] = $row[$valueField];    
  }
  return $map;
}

function extractColumn($rows, $colIndex) { 
  $col = array();
  foreach ($rows as $row) {
    if ($row != false) {
      $values = array_values($row);
      array_push($col, $values[$colIndex]);
    }
  }

  return $col;  
}

function getSQLColumn($sql) {
  return extractColumn(getSQLRows($sql), 0);
}

function execSQL($sql) {
  global $conn;

  mysql_query($sql, $conn) or die("Error in query: $sql." . mysql_error($conn));
}

function dbVals($vals) {
  $str = '';
  $i = 0;

  foreach ($vals as $val) {
    if ($i > 0) {
      $str .= ', ';  
    }

    $str .= dbVal($val);	  	
    $i++;
  }

  return $str;
}

function dbVal($val) {
  if (!isset($val) || trim($val) == '') {
    return 'NULL';
  }

  return '\'' . addslashes(trim($val)) . '\'';
}

function insertUsingAssoc($table, $dataAssoc, $fields) {
  $sql = "INSERT INTO $table (";

  foreach ($fields as $field) {
    if ($field != $fields[0]) {
      $sql .= ', ';
    }
    $sql .= $field; 	  	
  }

  $sql .= ') VALUES (';

  foreach ($fields as $field) {
    if ($field != $fields[0]) {
      $sql .= ', ';
    }

    if (isset($dataAssoc[$field])) { 	  	
      $val = $dataAssoc[$field]; 	  	  
    } else {
      $val = '';
    }
    $sql .= dbVal($val);
  }	

  $sql .= ')';

  return execSQL($sql);	
}

function updateUsingAssoc($table, $dataAssoc, $fields, $keyField, $keyValue) {
  $sql = "UPDATE $table SET ";

  foreach ($fields as $field) {
    if ($field != $fields[0]) {
      $sql .= ', ';
    }

    $sql .= $field;
    $sql .= ' = ';

    if (isset($dataAssoc[$field])) { 	  	
      $val = $dataAssoc[$field]; 	  	  
    } else {
      $val = '';
    }
    $sql .= dbVal($val);
  }	

  $sql .= " WHERE $keyField = " . dbVal($keyValue); 

  return execSQL($sql);	
}

function closeDBConn() {
  global $conn;

  mysql_close($conn);
}

/* Custom function to get a full list of results. */
function mysql_fetch_all($result) {
  $all = array();
  while ($all[] = mysql_fetch_assoc($result)) {}
  return $all;
}

?>
