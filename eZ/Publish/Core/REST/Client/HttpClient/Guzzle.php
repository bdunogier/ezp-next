<?php
/**
 * File containing the Guzzle class.
 *
 * @copyright Copyright (C) 2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace eZ\Publish\Core\REST\Client\HttpClient;

use eZ\Publish\Core\REST\Client\HttpClient;
use eZ\Publish\Core\REST\Common\Message;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Http\Message\Response;

class Guzzle implements HttpClient
{
    /** @var GuzzleClient */
    protected $guzzle;

    protected $username;

    protected $password;

    public function __construct( GuzzleClient $guzzle, $username, $password )
    {
        $this->guzzle = $guzzle;
        $this->username = $username;
        $this->password = $password;

        $this->guzzle->setDefaultOption( 'auth', array( $username, $password, 'Basic' ) );
        $this->guzzle->setDefaultOption( 'redirect.disable', true );

    }

    /**
     * Execute a HTTP request to the remote server
     *
     * Returns the result from the remote server.
     *
     * @param string $method
     * @param string $path
     * @param Message $message
     *
     * @return Message
     */
    public function request( $method, $path, Message $message = null )
    {
        if ( $message == null )
        {
            $message = new Message();
        }

        $request = $this->guzzle->createRequest(
            $method,
            $path,
            $message->headers,
            $message->body
        );

        try
        {
            return $this->toMessage(
                $this->guzzle->send( $request )
            );
        }
        catch ( ServerErrorResponseException $serverError )
        {
            return $this->toMessage( $serverError->getResponse() );
        }
    }

    /**
     * @param \Guzzle\Http\Message\Response $response
     *
     * @return Message
     */
    private function toMessage( Response $response )
    {
        $headers = array();
        foreach ( $response->getHeaders()->toArray() as $name => $values )
        {
            $headers[$name] = $values[0];
        }
        return new Message(
            $headers,
            $response->getBody(),
            $response->getStatusCode()
        );
    }
}
