<?php
function generateOtp($length = 5) {
    return str_pad(mt_rand(0, 99999), $length, '0', STR_PAD_LEFT);
}

function sendResponse($status, $message, $data = []) {
    echo json_encode(["status" => $status, "message" => $message, "data" => $data]);
    exit;
}
?>
