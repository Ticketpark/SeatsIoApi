<?php

namespace Ticketpark\SeatsIo\Tests;

use Ticketpark\SeatsIo\SeatsIo;
use Ticketpark\SeatsIo\Tests\TestBrowser\TestBrowser;

require_once('TestBrowser/TestBrowser.php');

/**
 * Please note that the tests currently only make sure
 * the correct url is called with the correct method.
 * On POST calls however, the tests don't check whether the
 * correct data is posted. Feel free to improve this.
 */
class SeatsIoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Exception
     */
    public function testNoSecretKey()
    {
        $seatsIo = new SeatsIo();
        $seatsIo->getCharts();
    }

    public function testDefaultBrowser()
    {
        $seatsIo = new SeatsIo();

        $this->assertInstanceOf('\Buzz\Browser', $seatsIo->getBrowser());
    }

    public function testSetSecretKey()
    {
        $seatsIo = new SeatsIo();
        $seatsIo->setSecretKey('foo');

        $this->assertSame('foo', $seatsIo->getSecretKey());
    }

    public function testSetBrowser()
    {
        $seatsIo = new SeatsIo();
        $seatsIo->setBrowser(new TestBrowser());

        $this->assertSame('success', $seatsIo->getBrowser()->testMe());
    }

    public function testConstructor()
    {
        $seatsIo = new SeatsIo('foo', new TestBrowser());

        $this->assertSame('foo', $seatsIo->getSecretKey());
        $this->assertSame('success', $seatsIo->getBrowser()->testMe());
    }

    public function testGetCharts()
    {
        $seatsIo = new SeatsIo('secretKey', $this->getBrowserMock('get'));

        $this->assertSame(
            'https://app.seats.io/api/charts/secretKey',
            $seatsIo->getCharts()
        );
    }

    public function testGetChartForEvent()
    {
        $seatsIo = new SeatsIo('secretKey', $this->getBrowserMock('get'));

        $this->assertSame(
            'https://app.seats.io/api/chart/secretKey/event/eventKey',
            $seatsIo->getChartForEvent('eventKey')
        );
    }

    public function testGetSingleChartDetails()
    {
        $seatsIo = new SeatsIo('secretKey', $this->getBrowserMock('get'));

        $this->assertSame(
            'https://app.seats.io/api/chart/chartKey.json',
            $seatsIo->getSingleChartDetails('chartKey')
        );
    }

    public function testCreateEvent()
    {
        $seatsIo = new SeatsIo('secretKey', $this->getBrowserMock('post'));

        $this->assertSame(
            'https://app.seats.io/api/linkChartToEvent',
            $seatsIo->createEvent('chartKey', 'eventKey')
        );
    }

    public function testCreateUser()
    {
        $seatsIo = new SeatsIo('secretKey', $this->getBrowserMock('post'));

        $this->assertSame(
            'https://app.seats.io/api/createUser',
            $seatsIo->createUser()
        );
    }

    public function testBook()
    {
        $seatsIo = new SeatsIo('secretKey', $this->getBrowserMock('post'));

        $this->assertSame(
            'https://app.seats.io/api/book',
            $seatsIo->book(array('A1', 'B2'), 'eventKey', 'orderId')
        );
    }

    public function testRelease()
    {
        $seatsIo = new SeatsIo('secretKey', $this->getBrowserMock('post'));

        $this->assertSame(
            'https://app.seats.io/api/release',
            $seatsIo->release(array('A1', 'B2'), 'eventKey')
        );
    }

    public function testChangeStatus()
    {
        $seatsIo = new SeatsIo('secretKey', $this->getBrowserMock('post'));

        $this->assertSame(
            'https://app.seats.io/api/changeStatus',
            $seatsIo->changeStatus(array('A1', 'B2'), 'eventKey', 'status')
        );
    }

    public function testGetOrder()
    {
        $seatsIo = new SeatsIo('secretKey', $this->getBrowserMock('get'));

        $this->assertSame(
            'https://app.seats.io/api/event/eventKey/orders/orderKey/secretKey',
            $seatsIo->getOrder('orderKey', 'eventKey')
        );
    }

    public function getBrowserMock($method)
    {
        $browser = $this->getMockBuilder('Buzz\Browser')
            ->disableOriginalConstructor()
            ->setMethods(array('get', 'post'))
            ->getMock();

        if ($method == 'get') {
            $expects = $this->once();
        } else {
            $expects = $this->never();
        }

        $browser->expects($expects)
            ->method('get')
            ->will($this->returnCallback(array($this, 'createResponse')));


        if ($method == 'post') {
            $expects = $this->once();
        } else {
            $expects = $this->never();
        }

        $browser->expects($expects)
            ->method('post')
            ->will($this->returnCallback(array($this, 'createResponse')));

        return $browser;
    }

    public function createResponse()
    {
        return $this->getResponseMock(func_get_arg(0));
    }

    public function getResponseMock($content)
    {
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getStatusCode', 'getContent'))
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(json_encode($content)));

        return $response;
    }

}