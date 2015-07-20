<?php
$url = parse_url(getenv("CLEARDB_DATABASE_URL"));

$mysqli_server = $url["host"];
$mysqli_user = $url["user"];
$mysqli_password = $url["pass"];
$mysqli_database = substr($url["path"], 1);

// The database connection
$conn = '';

function openDBConn() {
  global $mysqli_server;
  global $mysqli_user;
  global $mysqli_password;    
  global $mysqli_database;      

  if(usingAppFog()) {
    $credentials = getAppFogServicesCredentials('mysql-5.1', 'mysql');
    $mysqli_server = $credentials['host'].':'.$credentials['port'];
    $mysqli_user = $credentials['user'];
    $mysqli_password = $credentials['password'];
    $mysqli_database = $credentials['name'];
  }  

  global $conn;
  $conn = mysqli_connect($mysqli_server, $mysqli_user, $mysqli_password);
  if (!$conn) {
    die('Could not connect: ' . mysqli_connect_error());
  }

  if (!mysqli_select_db($conn, $mysqli_database)) {
    die('Could not set database: ' . mysqli_error($conn));    
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
  return mysqli_insert_id($conn);
}

function getSQLScalar($sql) {
  global $conn;

  $result = mysqli_query($conn, $sql) or die("Error in query: $sql." . mysqli_error($conn));
  $row = mysqli_fetch_row($result);  

  return $row[0];
}


function getSQLRows($sql) {
  global $conn;

  $result = mysqli_query($conn, $sql) or die("Error in query: $sql." . mysqli_error($conn));
  return mysqli_fetch_all($result, MYSQLI_ASSOC);  
}

// Returns a single row as an array that can be referenced as array["<field name>"] for the 
// first row of the query.
function getSQLRow($sql) {
  global $conn;

  $result = mysqli_query($conn, $sql) or die("Error in query: $sql." . mysqli_error($conn));
  return mysqli_fetch_assoc($result);
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

  mysqli_query($conn, $sql) or die("Error in query: $sql." . mysqli_error($conn));
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

  mysqli_close($conn);
}

?>
