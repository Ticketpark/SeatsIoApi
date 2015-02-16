<?php

namespace Ticketpark\SeatsIo;

use Buzz\Browser;
use Buzz\Message\Response;

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
    protected $lastError;

    /**
     * @var int
     */
    protected $lastErrorStatusCode;

    /**
     * Constructor
     *
     * @param string   $secretKey
     * @param Browser $browser
     */
    public function __construct($secretKey = null, Browser $browser = null)
    {
        $this->setSecretKey($secretKey);
        $this->setBrowser($browser);
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
     * @link   http://www.seats.io/docs/api#fetchingMetadata
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
     * @link   http://www.seats.io/docs/api#fetchingChartForEvent
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
     * This is an inofficial, undocumented feature mentioned in a
     * support chart with seats.io co-founder Ben Verbeken (<ben@seats.io>, @bverbeken)
     * Use with care!
     *
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
     * @link   http://www.seats.io/docs/api#creatingEvents
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
     * @link   http://www.seats.io/docs/api#creatingUsers
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
     * @link   http://www.seats.io/docs/api#bookingObjects
     * @param  array $objects
     * @param  string $event
     * @return mixed
     */
    public function book(array $objects, $eventKey, $orderKey = null)
    {
        $url = 'book';

        $data = array(
            'objects'   => $objects,
            'event'     => $eventKey,
            'orderKey'  => $orderKey,
            'secretKey' => $this->secretKey
        );

        return $this->post($url, $data);
    }

    /**
     * Release objects
     *
     * @link   http://www.seats.io/docs/api#releasingObjects
     * @param  array $objects
     * @param  string $event
     * @return mixed
     */
    public function release(array $objects, $eventKey)
    {
        $url = 'release';

        $data = array(
            'objects'   => $objects,
            'event'     => $eventKey,
            'secretKey' => $this->secretKey
        );

        return $this->post($url, $data);
    }

    /**
     * Change object status
     *
     * @link   http://www.seats.io/docs/api#releasingObjects
     * @param  array $objects
     * @param  string $event
     * @return mixed
     */
    public function changeStatus(array $objects, $eventKey, $status)
    {
        $url = 'changeStatus';

        $data = array(
            'objects'   => $objects,
            'eventKey'  => $eventKey,
            'status'    => $status,
            'secretKey' => $this->secretKey
        );

        return $this->post($url, $data);
    }

    /**
     * Get objects within an order
     *
     * @link   http://www.seats.io/docs/api#orders
     * @param  string $orderKey
     * @param  string $eventKey
     * @return mixed
     */
    public function getOrder($orderKey, $eventKey)
    {
        $url = 'event/' . $eventKey . '/orders/' . $orderKey . '/' . $this->secretKey;

        return $this->get($url);
    }

    /**
     * Get last error
     *
     * @return array
     */
    public function getLastError()
    {
        return array(
            'statusCode' => $this->lastErrorStatusCode,
            'message'    => $this->lastError,
        );
    }

    /**
     * Get data from url
     *
     * @param $url
     * @return mixed
     */
    protected function get($url)
    {
        $this->checkSetup();

        $url = self::BASE_URL . $url;

        return $this->handleResponse(
            $this->getBrowser()->get($url)
        );
    }

    /**
     * Post data to url
     *
     * @param $url
     * @return mixed
     */
    protected function post($url, $data = null)
    {
        $this->checkSetup();

        $url = self::BASE_URL . $url;

        return $this->handleResponse(
            $this->getBrowser()->post($url, array(), json_encode($data))
        );
    }

    /**
     * Handle response
     *
     * @param Response $response
     * @return mixed
     */
    protected function handleResponse(Response $response)
    {
        if ($response->isSuccessful()) {

            $content = $response->getContent();

            // If the content is not a json string, it is probably a compressed response
            if (!$this->isJson($content)) {

                if (!$content = gzdecode($content)) {

                    throw \Exception('Response could not be interpreted.');
                }
            }

            if (!$this->isJson($content)) {

                throw \Exception('Response does not contain valid json content.');
            }

            return json_decode($content, true);
        }

        $this->setLastError($response->getContent(), $response->getStatusCode());

        return false;
    }

    /**
     * Set last error
     *
     * @param string $error
     * @param int    $statusCode
     */
    protected function setLastError($error, $statusCode)
    {
        $this->lastError = $error;
        $this->lastErrorStatusCode = $statusCode;
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
     * @throws \Exception
     */
    protected function checkSetup()
    {
        if (null == $this->secretKey) {

            throw new \Exception('You must define a secretKey with setSecretKey().');
        }
    }

    /**
     * Check if a string is a json
     *
     * @link   http://stackoverflow.com/a/6041773/407697
     * @param  string $string
     * @return bool
     */
    protected function isJson($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}