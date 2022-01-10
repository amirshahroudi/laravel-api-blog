<?php
/**
 * Created by PhpStorm.
 * User: amirpc
 * Date: 1/1/2022
 * Time: 10:10 PM
 */

namespace App\Helpers;


trait APIResponseHelper
{
    public function send_custom_response($data, $message, $responseCode, $is_success)
    {
        $response = [];
        if (!is_null($data)) {
            $response = array_merge($response, ['data' => $data]);
        }
        if (!is_null($message)) {
            $response = array_merge($response, ['message' => $message]);
        }
        if (!is_null($is_success)) {
            $response = array_merge($response, ['success' => $is_success]);
        }
        return response()->json($response, $responseCode);
    }
}