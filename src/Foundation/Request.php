<?php
/**
 * Copyright
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HuangYi\Swoole\Foundation;

use Illuminate\Http\Request as IlluminateRequest;
use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\ParameterBag;

class Request extends IlluminateRequest
{
    /**
     * This function is copy from
     * \Illuminate\Http\Request::capture()
     *
     * @param \Swoole\Http\Request $swooleRequest
     * @param float $requestStart
     * @return \Illuminate\Http\Request
     * @throws \LogicException
     */
    public static function swooleCapture(SwooleRequest $swooleRequest, $requestStart)
    {
        list($get, $post, $cookie, $files, $server, $content)
            = self::formatSwooleRequest($swooleRequest, $requestStart);

        static::enableHttpMethodParameterOverride();

        $request = self::createFromSwooleGlobal($get, $post, $cookie, $files, $server, $content);

        return static::createFromBase($request);
    }

    /**
     * This function is copy from
     * \Symfony\Component\HttpFoundation\Request::createFromGlobals()
     *
     * @param array $get
     * @param array $post
     * @param array $cookie
     * @param array $files
     * @param array $server
     * @param mixed $content
     * @return array|mixed|static
     * @throws \LogicException
     */
    protected static function createFromSwooleGlobal($get, $post, $cookie, $files, $server, $content)
    {
        // With the php's bug #66606, the php's built-in web server
        // stores the Content-Type and Content-Length header values in
        // HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH fields.
        if ('cli-server' === PHP_SAPI) {
            if (array_key_exists('HTTP_CONTENT_LENGTH', $server)) {
                $server['CONTENT_LENGTH'] = $server['HTTP_CONTENT_LENGTH'];
            }
            if (array_key_exists('HTTP_CONTENT_TYPE', $server)) {
                $server['CONTENT_TYPE'] = $server['HTTP_CONTENT_TYPE'];
            }
        }

        $request = new static($get, $post, [], $cookie, $files, $server, $content);

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return $request;
    }

    /**
     * Format swoole request parameters.
     *
     * @param \Swoole\Http\Request $request
     * @param float $requestStart
     * @return array
     */
    protected static function formatSwooleRequest(SwooleRequest $request, $requestStart)
    {
        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $files = isset($request->files) ? $request->files : [];
        $cookie = isset($request->cookie) ? $request->cookie : [];
        $header = isset($request->header) ? $request->header : [];
        $server = isset($request->server) ? $request->server : [];
        $content = $request->rawContent();

        $server = self::formatSwooleServerArray($server, $header, $requestStart);

        return [$get, $post, $files, $cookie, $server, $content];
    }

    /**
     * Format swoole's server array.
     * This function will merge headers into SERVER array,
     * and set REQUEST_START value.
     *
     * @param array $server
     * @param array $header
     * @param float $requestStart
     * @return array
     */
    protected static function formatSwooleServerArray(array $server, array $header, $requestStart)
    {
        $__SERVER = [];

        foreach ($server as $key => $value) {
            $key = strtoupper($key);
            $__SERVER[$key] = $value;
        }

        foreach ($header as $key => $value) {
            $key = str_replace('-', '_', $key);
            $key = strtoupper($key);

            if (! in_array($key, ['REMOTE_ADDR', 'SERVER_PORT', 'HTTPS'])) {
                $key = 'HTTP_' . $key;
            }

            $__SERVER[$key] = $value;
        }

        // set request start time
        $__SERVER['REQUEST_START'] = $requestStart;

        return $__SERVER;
    }
}