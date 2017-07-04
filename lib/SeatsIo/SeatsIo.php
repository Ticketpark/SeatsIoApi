<?php

namespace Ticketpark\SeatsIo;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Psr\Log\LoggerInterface;
use Ticketpark\SeatsIo\Exception\BadSetupException;
use Ticketpark\SeatsIo\Exception\UnsuccessfulResponseException;

/**
 * SeatsIo
 *
 * @author Manuel Reinhard <manuel.reinhard@ticketpark.ch>
 */

class SeatsIo
{
    /**
     * @const string BASE_URL
     */
    const BASE_URL = 'https://app.seats.io/api/';

    /**
     * @const string BASE_URL_STAGING
     */
    const BASE_URL_STAGING = 'https://app-staging.seats.io/api/';

    /**
     * @var string $secretKey
     */
    protected $secretKey;

    /**
     * @var Browser $browser
     */
    protected $browser;

    /**
     * The default browser. Used whenever no specific browser was injected.
     * @var Browser $defaultBrowser
     */
    protected $defaultBrowser;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param string   $secretKey
     * @param Browser $browser
     */
    public function __construct($secretKey = null, Browser $browser = null, $stagingEnvironment = false, LoggerInterface $logger = null)
    {
        $this->setSecretKey($secretKey);
        $this->setBrowser($browser);
        $this->baseUrl = self::BASE_URL;
        $this->logger = $logger;

        if ($stagingEnvironment) {
            $this->baseUrl = self::BASE_URL_STAGING;
        }
    }

    /**
     * Set secret key
     *
     * @param  string $secretKey
     * @return $this
     */
    public function setSecretKey($secretKey = null)
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    /**
     * Get secret key
     *
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * Set browser
     *
     * @param  Browser $browser
     * @return $this
     */
    public function setBrowser(Browser $browser = null)
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * Get browser
     *
     * @return Browser
     */
    public function getBrowser()
    {
        if (null == $this->browser) {

            return $this->getDefaultBrowser();
        }

        return $this->browser;
    }

    /**
     * Get charts
     *
     * @link   http://www.seats.io/docs/api#api-reference-charts-list-charts-for-a-user
     * @return mixed
     */
    public function getCharts()
    {
        $url = 'charts/' . $this->secretKey;

        return $this->get($url);
    }

    /**
     * Fetch chart for event
     *
     * @link   http://www.seats.io/docs/api#api-reference-charts-fetching-the-chart-linked-to-an-event
     * @param  string $eventKey
     * @return mixed
     */
    public function getChartForEvent($eventKey)
    {
        $url = 'chart/' . $this->secretKey . '/event/' . $eventKey;

        return $this->get($url);
    }

    /**
     * Get single chart details
     *
     * @link   http://www.seats.io/docs/api#api-reference-charts
     * @param  string $chartKey
     * @return mixed
     */
    public function getSingleChartDetails($chartKey)
    {
        $url = 'chart/' . $chartKey . '.json';

        return $this->get($url);
    }

    /**
     * Create event in chart
     *
     * @link   http://www.seats.io/docs/api#api-reference-events
     * @param  string $chartKey
     * @param  string $eventKey
     * @return mixed
     */
    public function createEvent($chartKey, $eventKey)
    {
        $url = 'linkChartToEvent';

        $data = array(
            'chartKey' => $chartKey,
            'eventKey' => $eventKey,
            'secretKey' => $this->secretKey
        );

        return $this->post($url, $data);
    }

    /**
     * Create user
     *
     * @link   http://www.seats.io/docs/api#api-reference-users
     * @return mixed
     */
    public function createUser()
    {
        $url = 'createUser';

        return $this->post($url);
    }

    /**
     * Book objects
     *
     * @link   http://www.seats.io/docs/api#api-reference-booking-and-releasing-objects
     * @param  array $objects
     * @param  string $event
     * @return mixed
     */
    public function book(array $objects, $eventKey, $orderKey = null, $reservationToken = null)
    {
        $url = 'book';

        $data = array(
            'objects'   => $objects,
            'event'     => $eventKey,
            'orderKey'  => $orderKey,
            'reservationToken'  => $reservationToken,
            'secretKey' => $this->secretKey
        );

        return $this->post($url, $data);
    }

    /**
     * Release objects
     *
     * @link   http://www.seats.io/docs/api#api-reference-booking-and-releasing-objects
     * @param  array $objects
     * @param  string $event
     * @return mixed
     */
    public function release(array $objects, $eventKey, $reservationToken = null)
    {
        $url = 'release';

        $data = array(
            'objects'   => $objects,
            'event'     => $eventKey,
            'reservationToken'  => $reservationToken,
            'secretKey' => $this->secretKey
        );

        return $this->post($url, $data);
    }

    /**
     * Change object status
     *
     * @link   http://www.seats.io/docs/api#api-reference-booking-and-releasing-objects
     * @param  array $objects
     * @param  string $event
     * @return mixed
     */
    public function changeStatus(array $objects, $eventKey, $status, $reservationToken = null)
    {
        $url = 'changeStatus';

        $data = array(
            'objects'   => $objects,
            'event'     => $eventKey,
            'status'    => $status,
            'reservationToken'  => $reservationToken,
            'secretKey' => $this->secretKey
        );

        return $this->post($url, $data);
    }

    /**
     * Get objects within an order
     *
     * @link   http://www.seats.io/docs/api#api-reference-booking-and-releasing-objects-orders
     * @param  string $orderKey
     * @param  string $eventKey
     * @return array|string
     */
    public function getOrder($orderKey, $eventKey)
    {
        $url = 'event/' . $eventKey . '/orders/' . $orderKey . '/' . $this->secretKey;

        return $this->get($url);
    }

    /**
     * Get data from url
     *
     * @param  string $url
     * @return array|string
     */
    protected function get($url)
    {
        $this->checkSetup();

        $url = $this->baseUrl . $url;

        $response = $this->getBrowser()->get($url);

        if ($this->logger) {
            $this->logger->debug('GET ' . $response->getStatusCode().' '.$url);
        }

        return $this->handleResponse($response);
    }

    /**
     * Post data to url
     *
     * @param  $url
     * @return array|string
     */
    protected function post($url, $data = null)
    {
        $this->checkSetup();

        $url = $this->baseUrl . $url;

        $response = $this->getBrowser()->post($url, array(), json_encode($data));
        if ($this->logger) {
            $this->logger->debug('POST ' . $response->getStatusCode().' '. $response->getContent() .' ' . $url . ' ' . json_encode($data));
        }

        return $this->handleResponse($response);
    }


    /**
     * Handle response
     *
     * @param  Response $response
     * @return array|string
     * @throws UnsuccessfulResponseException
     */
    protected function handleResponse(Response $response)
    {
        if (!$response->isSuccessful()) {
            if ($this->logger) {
                $this->logger->critical('seats.io request failed with status: ' . $response->getStatusCode());
            }

            throw new UnsuccessfulResponseException('seats.io request failed with status: ' . $response->getStatusCode());
        }

        $content = $response->getContent();

        if ('' === $content) {

            return $content;
        }

        try {
            $content = gzdecode($content);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->debug('seats.io handleResponse: not gzencoded: ' . $e->getMessage());
            }
        }

        $jsonDecoded = json_decode($content, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $jsonDecoded;
        } else {
            if ($this->logger) {
                $this->logger->debug('seats.io handleResponse: not json: ' . json_last_error_msg());
            }
        }

        return $content;
    }

    /**
     * @return Browser
     */
    protected function getDefaultBrowser()
    {
        if (null == $this->defaultBrowser) {
            $this->defaultBrowser = new Browser();
        }

        return $this->defaultBrowser;
    }

    /**
     * @throws BadSetupException
     */
    protected function checkSetup()
    {
        if (null == $this->secretKey) {

            throw new BadSetupException('You must define a secretKey with setSecretKey().');
        }
    }
}
