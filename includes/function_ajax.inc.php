<?php

// Helper functions for ajax related, like generating answer

function ajaxError($reason)
{
    http_response_code(400);
    header('Content-Type: application/json');
    $result = [];
    $result['status'] = 'error';
    $result['message'] = $reason;
    echo json_encode($result);
}

function ajaxSuccess($data=NULL)
{
    header('Content-Type: application/json');
    $result = [];
    $result['status'] = 'success';
    $result['data'] = $data;
    echo json_encode($result);
}
