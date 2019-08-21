<?php
/**
 * @author Alexandre DEBUSSCHERE <alexandre@common-services.com>
 */
 
namespace Borsch\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{

    /** @var string */
    protected $request_target;

    /** @var string */
    protected $method;

    /** @var array */
    protected $allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH', 'HEAD', 'CONNECT'];

    /**
     * Request constructor.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request.
     */
    public function __construct(string $method, $uri)
    {
        if (!is_string($uri) && !$uri instanceof UriInterface) {
            throw new \InvalidArgumentException('Uri must be a string or an instance of UriInterface.');
        }

        parent::__construct();

        $this->method = $method;
        $this->uri = is_string($uri) ? new Uri($uri) : $uri;
        $this->request_target = $this->uri->getPath() ?: '/'; // TODO check it out
    }

    /**
     * Retrieves the message's request target.
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget(): string
    {
        return $this->request_target;
    }

    /**
     * Return an instance with the specific request-target.
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param string $request_target
     * @return static
     */
    public function withRequestTarget($request_target): self
    {
        if (!is_string($request_target) || strpos($request_target, ' ') !== false) {
            throw new \InvalidArgumentException('Request target cannot contain whitespace.');
        }

        $request = clone $this;
        $request->request_target = $request_target;

        return $request;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        if (!is_string($method) || !in_array(strtoupper($method), $this->allowed_methods)) {
            throw new \InvalidArgumentException('Method is invalid.');
        }

        $request = clone $this;
        $request->method = strtoupper($method);

        return $request;
    }

    /**
     * Retrieves the URI instance.
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserve_host Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserve_host = false): self
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $request = clone $this;
        $request->uri = $uri;

        if ((!$preserve_host || !$request->hasHeader('Host')) && strlen($request->uri->getHost())) {
            $host = trim(sprintf(
                '%s:%s',
                $request->uri->getHost(),
                $request->uri->getPort()
            ), ':');

            $request->withHeader('Host', $host);

            // Place Host as first header.
            $request->headers = ['Host' => [$host]] + $request->headers;
        }

        return $request;
    }
}
