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

class ResponseFactory
{
    /**
     * @var \Swoole\Http\Response
     */
    protected $response;

    /**
     * Response type.
     *
     * @var string
     */
    protected $type = 'text';

    /**
     * Response content.
     *
     * @var string
     */
    protected $content = '';

    /**
     * Create swoole response from illuminate response.
     *
     * @param \Swoole\Http\Response $swooleResponse
     * @param mixed $illuminateResponse
     * @return \HuangYi\Swoole\Foundation\ResponseFactory
     * @throws \InvalidArgumentException
     */
    public static function createFromIlluminate(SwooleResponse $swooleResponse, $illuminateResponse)
    {
        $response = new static;

        $response->setResponse($swooleResponse);

        if ($illuminateResponse instanceof SymfonyResponse) {
            $response->initFromIlluminateResponse($illuminateResponse);
        } else {
            $response->setContent((string) $illuminateResponse);
        }

        return $response;
    }

    /**
     * Init swoole response data from illuminate response.
     *
     * @param \Symfony\Component\HttpFoundation\Response $illuminateResponse
     * @throws \InvalidArgumentException
     */
    protected function initFromIlluminateResponse(SymfonyResponse $illuminateResponse)
    {
        // headers
        foreach ($illuminateResponse->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $this->response->header($name, $value);
            }
        }

        // cookies
        foreach ($illuminateResponse->headers->getCookies() as $cookie) {
            $this->response->cookie(
                $cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(),
                $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }

        // status
        $this->response->status($illuminateResponse->getStatusCode());

        // stream
        if ($illuminateResponse instanceof StreamedResponse) {
            //  No processing currently.
            $this->setType('stream');
            $this->setContent('');
        } // file
        elseif ($illuminateResponse instanceof BinaryFileResponse) {
            $this->setType('file');
            $this->setContent($illuminateResponse->getFile()->getPathname());
        } // text
        else {
            $this->setType('text');
            $this->setContent($illuminateResponse->getContent());
        }
    }

    /**
     * End.
     */
    public function send()
    {
        $type = $this->getType();
        $content = $this->getContent();

        if ($type == 'file') {
            $this->response->sendfile($content);
        } else {
            $this->response->end($content);
        }
    }

    /**
     * @return \Swoole\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param \Swoole\Http\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}