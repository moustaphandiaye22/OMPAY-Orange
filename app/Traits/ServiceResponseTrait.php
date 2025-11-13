<?php

namespace App\Traits;

/**
 * Trait for standardizing service response formats
 */
trait ServiceResponseTrait
{
    /**
     * Create a success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return array
     */
    protected function successResponse($data = null, string $message = '', int $status = 200): array
    {
        $response = [
            'success' => true,
            'status' => $status
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Create an error response
     *
     * @param string $errorCode
     * @param string $message
     * @param array $details
     * @param int $status
     * @return array
     */
    protected function errorResponse(string $errorCode, string $message, array $details = [], int $status = 400): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details
            ],
            'status' => $status
        ];
    }
}