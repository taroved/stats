<?php
require '../config.php';
require '../table.php';

function auth() {
    if (!isset($_SERVER['PHP_AUTH_USER']))
    {
        header('WWW-Authenticate: Basic realm="Statistic server authentication"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'User/password is required';
        exit;
    }
    else
    {
        global $config;
        if ($_SERVER['PHP_AUTH_USER'] == $config['auth']['user'] && $_SERVER['PHP_AUTH_PW'] == $config['auth']['password']) {
            return true;
        }
        else {
            echo 'User/password is incorrect';
        }
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST' && $_SERVER['REQUEST_URI'] == '/') { // insert record
    $json = file_get_contents('php://input');
    $data = json_decode($json);
    $errors = [];
    if (!Table::validate_record($data, $errors))
    {
        echo json_encode(array('status' => 'FAILED', 'errors' => $errors));
    }
    else
    {
        try {
            $count = Table::save_record($data);
            echo json_encode(array('status' => 'OK'));
        }
        catch (Exception $e) {
            echo json_encode(array('status' => 'FAILED', 'errors' => ['PHP Exception'. $e]));
        }
    }
}
elseif ($method == 'GET' && preg_match('/\/dump\/\d{6}\.json/', $_SERVER['REQUEST_URI'])) { //dump records
    if (auth()) {
        $m = null;
        preg_match('/\/dump\/(\d{6})/', $_SERVER['REQUEST_URI'], $m);
        Table::dump_records_table_json('record_'. $m[1]);
    }
}
elseif ($method == 'GET' && preg_match('/\/list/', $_SERVER['REQUEST_URI'])) { // list existed dates
    if (auth()) {
        echo json_encode(Table::get_table_list());
    }
}
elseif ($method == 'POST' && preg_match('/\/delete\/\d{6}/', $_SERVER['REQUEST_URI'])) { // delete date dates
    if (auth()) {
        $m = null;
        preg_match('/\/delete\/(\d{6})/', $_SERVER['REQUEST_URI'], $m);
        Table::delete_table('record_' . $m[1]);
        echo json_encode(array('status' => 'OK'));
    }
}
else {
    echo 'Invalid request method or request URI';
}