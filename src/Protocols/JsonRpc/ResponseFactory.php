<?php

namespace HuangYi\Swoole\Protocols\JsonRpc;

class ResponseFactory
{
    /**
     * Ok.
     *
     * @param mixed $result
     * @param mixed $id
     * @return \HuangYi\Swoole\Protocols\JsonRpc\Response
     */
    public function ok($result, $id)
    {
        $attributes = [
            'result' => $result,
            'id' => $id,
        ];

        return self::make($attributes);
    }

    /**
     * Notification.
     *
     * @param $method
     * @return \HuangYi\Swoole\Protocols\JsonRpc\Response
     */
    public function notification($method)
    {
        $attributes = ['method' => $method];

        return self::make($attributes);
    }

    /**
     * Error invalid params.
     *
     * @param array $errors
     * @param mixed $id
     * @param string $message
     * @return \HuangYi\Swoole\Protocols\JsonRpc\Response
     */
    public static function errorInvalidParams(array $errors = [], $id = null, $message = 'Invalid method parameter(s).')
    {
        return self::error(-32602, $message, $errors, $id);
    }

    /**
     * Response error.
     *
     * @param int $code
     * @param string $message
     * @param array $errors
     * @param mixed $id
     * @return \HuangYi\Swoole\Protocols\JsonRpc\Response
     */
    public static function error($code, $message, array $errors = null, $id = null)
    {
        $attributes = [
            'error' => ['code' => $code, 'message' => $message],
            'id' => $id,
        ];

        if (! empty($errors)) {
            $attributes['error']['data'] = $errors;
        }

        return self::make($attributes);
    }

    /**
     * Make response.
     *
     * @param array $attributes
     * @return \HuangYi\Swoole\Protocols\JsonRpc\Response
     */
    public static function make(array $attributes)
    {
        $payload = Payload::make($attributes);

        return new Response($payload);
    }
}
