<?php

class Table {

    public static function validate_record($data, &$errors)
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

    public static function save_record($record)
    {
        $record->created = time();

        global $config;
        if ($config['redis']['enabled']) {
            $redis = new Redis();
            $redis->connect($config['redis']['host'] .':'. $config['redis']['port']);

            $redis->lPush('records', json_encode($record)); //push to queue

            if ($redis->lLen('records') > $config['redis']['size']) { //if queue is full
                $pipe = $redis->multi(Redis::PIPELINE);
                for ($i=0; $i<$config['redis']['size']; $i++) {
                    $pipe->rPop('records');
                }
                $records = array_filter($pipe->exec(), function($r) { //filter empty results
                    return $r != -1;
                });
                if ($records)
                    self::insert_records(array_map(function($r) {
                        return json_decode($r);
                    },$records));
            }
        }
        else {
            self::insert_records([$record]);
        }
    }

    private static function insert_records($records) {
        global $config;
        $dbconf = $config['mysql'];
        $db = new PDO('mysql:host='. $dbconf['host'] .';dbname='. $dbconf['database'],
            $dbconf['username'], $dbconf['password'], array(PDO::ATTR_PERSISTENT => true));
        //1. check if table exists for the date
        $date_part = date('ymd');
        $tablename = "record_$date_part";
        $table_exists = false;
        $q = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '". $dbconf['database'] ."' AND TABLE_NAME = '$tablename'";
        foreach ($db->query($q) as $x)
            $table_exists = true;

        if (!$table_exists) { //create the table
            self::create_records_table($db, $tablename);
        }

        //2. insert to the table
        $q = "INSERT INTO `$tablename` (`player_id`, `time`, `device_id`, `platform`, `data`, `created`)".
            "VALUES";
        $rows = array();
        foreach ($records as $r) {
            $rows[] = "(".
                $db->quote($r->player_id).",".
                $db->quote($r->time).",".
                $db->quote($r->device_id).",".
                $db->quote($r->platform).",".
                $db->quote($r->data).",".
                $db->quote($r->created).")";
        }
        $q .= join(',', $rows);
        $db->exec($q);
    }

    private static function create_records_table(&$db, $tablename)
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
}