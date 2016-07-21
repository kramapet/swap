<?php

/*
 * This file is part of Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Tests\Provider;

abstract class AbstractProviderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Create a mocked Http adapter.
     *
     * @param string $url     The url
     * @param string $content The body content
     *
     * @return \Ivory\HttpAdapter\HttpAdapterInterface
     */
    protected function getHttpAdapterMock($url, $content)
    {
        $adapter = $this->getMock('Ivory\HttpAdapter\HttpAdapterInterface');

        $adapter
            ->expects($this->once())
            ->method('get')
            ->with($url)
            ->will($this->returnValue($this->createResponse($content)));

        return $adapter;
    }

    /**
     * Create a mocked Http adapter with multiple urls.
     *
     * @param array $urls     list of URLs
     * @param array $contents list of contents
     *
     * @return \Ivory\HttpAdapter\HttpAdapterInterface
     */
    protected function getHttpAdapterMockWithMultipleUrls($urls, $contents)
    {
        $map = [];
        foreach ($urls as $i => $url) {
            $map[] = [$url, [], $this->createResponse($contents[$i])];
        }

        $adapter = $this->getMock('Ivory\HttpAdapter\HttpAdapterInterface');

        $adapter
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        return $adapter;
    }

    /**
     * Create response for Ivory\HttpAdapter\HttpAdapterInterface.
     *
     * @param string $content
     *
     * @return Ivory\HttpAdapter\Message\ResponseInterface
     */
    private function createResponse($content)
    {
        $body = $this->getMock('Psr\Http\Message\StreamInterface');
        $body
            ->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($content));

        $response = $this->getMock('Ivory\HttpAdapter\Message\ResponseInterface');
        $response
            ->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue($body));

        return $response;
    }
}
