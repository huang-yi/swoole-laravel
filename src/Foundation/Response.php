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

use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Response extends SwooleResponse
{
    /**
     * Response type.
     *
     * @var string
     */
    protected $_type = 'text';

    /**
     * Response content.
     *
     * @var string
     */
    protected $_content = '';

    /**
     * Create swoole response from illuminate response.
     *
     * @param $illuminateResponse
     * @return \HuangYi\Swoole\Foundation\Response
     * @throws \InvalidArgumentException
     */
    public static function createFromIlluminate($illuminateResponse)
    {
        $request = new static;

        if ($illuminateResponse instanceof SymfonyResponse) {
            $request->initFromIlluminateResponse($illuminateResponse);
        } else {
            $request->_setContent((string) $illuminateResponse);
        }

        return $request;
    }

    /**
     * Init swoole response data from illuminate response.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @throws \InvalidArgumentException
     */
    protected function initFromIlluminateResponse(SymfonyResponse $response)
    {
        // headers
        foreach ($response->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $this->header($name, $value);
            }
        }

        // cookies
        foreach ($response->headers->getCookies() as $cookie) {
            $this->cookie(
                $cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(),
                $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }

        // status
        $this->status($response->getStatusCode());

        // stream
        if ($response instanceof StreamedResponse) {
            //  No processing currently.
            $this->_setType('stream');
            $this->_setContent('');
        } // file
        elseif ($response instanceof BinaryFileResponse) {
            $this->_setType('file');
            $this->_setContent($response->getFile()->getPathname());
        } // text
        else {
            $this->_setContent($response->getContent());
        }
    }

    /**
     * End.
     */
    public function end()
    {
        $type = $this->_getType();
        $content = $this->_getContent();

        if ($type == 'file') {
            $this->sendfile($content);
        } else {
            $this->end($content);
        }
    }

    /**
     * @return string
     */
    public function _getType()
    {
        return $this->_type;
    }

    /**
     * @param string $type
     */
    public function _setType($type)
    {
        $this->_type = $type;
    }

    /**
     * @return string
     */
    public function _getContent()
    {
        return $this->_content;
    }

    /**
     * @param string $content
     */
    public function _setContent($content)
    {
        $this->_content = $content;
    }
}