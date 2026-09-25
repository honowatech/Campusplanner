<?php

namespace App\Traits;

trait HttpResponses
{
    public function success($data = null, $message = null, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function error($data = null, $message = null, $code = 400)
    {
        return response()->json([
            'status' => 'failed',
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
