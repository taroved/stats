<?
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json);
    $errors = [];
    if (!validate_record($data, $errors))
    {
        echo json_encode(array('status' => 'FAILED', 'errors' => $errors));
    }
    else
    {
        $count = save_record($data);
        if ($count == 1) {
            echo json_encode(array('status' => 'OK'));
        }
        else {
            echo json_encode(array('status' => 'FAILED', 'errors' => ['Wrong save result']));
        }
    }
}
else {
    echo 'Failed';
}

function validate_record($data, &$errors)
{
    if (!isset($data->player_id)) {
        $errors[] = 'player_id is required';
    }
    if (!isset($data->time)) {
        $errors[] = 'time is required';
    }
    if (!isset($data->device_id)) {
        $errors[] = 'device_id is required';
    }
    elseif (strlen($data->device_id) != 64)
    {
        $errors[] = 'device_id has wrong length. (Must be 64 chars)';
    }
    if (!isset($data->platform)) {
        $errors[] = 'platform is required';
    }

    return count($errors) == 0;
}

function save_record($data)
{
    require('../config.php');
    $db = new PDO('mysql:host='. $config['host'] .';dbname='. $config['database'],
        $config['username'], $config['password'], array(PDO::ATTR_PERSISTENT => true));

    //1. check if table exists for the date
    $date_part = date('ymd');
    $tablename = "record_$date_part";
    $table_exists = false;
    $q = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '". $config['database'] ."' AND TABLE_NAME = '$tablename'";
    foreach ($db->query($q) as $x)
        $table_exists = true;

    if (!$table_exists) { //create the table
        create_records_table($db, $tablename);
    }
    //2. insert to the table
    $q = "INSERT INTO `$tablename` (`player_id`, `time`, `device_id`, `platform`, `data`, `created`)".
        "VALUES(".
        $db->quote($data->player_id).",".
        $db->quote($data->time).",".
        $db->quote($data->device_id).",".
        $db->quote($data->platform).",".
        $db->quote($data->data).",".
        time().")";
    $result = $db->exec($q);
    return $result;
}

function create_records_table(&$db, $tablename)
{
    $q = 'CREATE TABLE IF NOT EXISTS `'. $tablename .'` (
  `player_id` varchar(64) COLLATE utf8_bin NOT NULL,
  `time` int(11) NOT NULL,
  `device_id` varchar(64) COLLATE utf8_bin NOT NULL,
  `platform` varchar(64) COLLATE utf8_bin NOT NULL,
  `data` varchar(3000) COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;';
    $db->exec($q);
}