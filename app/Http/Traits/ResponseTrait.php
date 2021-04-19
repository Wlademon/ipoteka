<?php
namespace App\Http\Traits;


use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

trait ResponseTrait
{
    protected function successResponse($responseObject = null, $headers = [])
    {
        $response = [
            'success' => true
        ];
        if ($responseObject !== null) {
            $response['data'] = $responseObject;
        }

        Log::info('successResponse. ', [$response]);

        return response($response, 200, $headers);
    }

    protected function errorResponse($errorCode, array $fields = [], $headers = [], $custom_message = null)
    {
        $response = [
            'success' => false,
            'errors' => [
                'code' => $errorCode,
                'desc' => $custom_message ?: trans('errors.' . $errorCode),
            ],
        ];

        if (count($fields) > 0) {
            $response['error']['error_fields'] = $fields;
        }

        Log::info('errorResponse. ', [$response]);

        return response($response, Response::HTTP_BAD_REQUEST, $headers);
    }
}
