<?
require '../config.php';
require '../table.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
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
else {
    echo 'Failed';
}