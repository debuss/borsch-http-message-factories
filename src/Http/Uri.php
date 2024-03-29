<?php
/**
 * @author Alexandre DEBUSSCHERE <alexandre@common-services.com>
 */
 
namespace Borsch\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{

    /** @var string */
    protected $scheme;

    /** @var string */
    protected $user;

    /** @var string */
    protected $pass;

    /** @var string */
    protected $host;

    /** @var string */
    protected $port;

    /** @var string */
    protected $path;

    /** @var string */
    protected $query;

    /** @var string */
    protected $fragment;

    /**
     * Uri constructor.
     *
     * @param string $uri
     * @throws \InvalidArgumentException
     */
    public function __construct(string $uri = '')
    {
        if (strlen($uri) && filter_var($uri, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Uri is not valid.');
        }

        foreach (parse_url($uri) as $prop => $value) {
            $this->{$prop} = $value;
        }
    }

    /**
     * Retrieve the scheme component of the URI.
     * If no scheme is present, this method MUST return an empty string.
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme(): string
    {
        return (string)$this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     * If no authority information is present, this method MUST return an empty
     * string.
     * The authority syntax of the URI is:
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority(): string
    {
        if (!strlen($this->getHost())) {
            return '';
        }

        $user_info = $this->getUserInfo();
        $port = $this->getPort();

        return sprintf(
            '%s%s%s',
            strlen($user_info) ? ($user_info.'@') : '',
            $this->getHost(),
            $port !== null ? (':'.$port) : ''
        );
    }

    /**
     * Retrieve the user information component of the URI.
     * If no user information is present, this method MUST return an empty
     * string.
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo(): string
    {

        return trim(sprintf('%s:%s', $this->user, $this->pass), ':');
    }

    /**
     * Retrieve the host component of the URI.
     * If no host is present, this method MUST return an empty string.
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost(): string
    {
        return (string)$this->host;
    }

    /**
     * Retrieve the port component of the URI.
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Retrieve the path component of the URI.
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath(): string
    {
        return (string)$this->path;
    }

    /**
     * Retrieve the query string of the URI.
     * If no query string is present, this method MUST return an empty string.
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery(): string
    {
        return (string)$this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     * If no fragment is present, this method MUST return an empty string.
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment(): string
    {
        return (string)$this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return static A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme): self
    {
        if ($this->scheme == $scheme) {
            return $this;
        }

        if (!in_array(strtolower($scheme), ['', 'http', 'https'])) {
            throw new \InvalidArgumentException('Scheme must be one of : "", "http" or "https"');
        }

        $uri = clone $this;
        $uri->scheme = $scheme;

        return $uri;
    }

    /**
     * Return an instance with the specified user information.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null): self
    {
        $info = $user;
        if (strlen($password)) {
            $info .= ':' . $password;
        }

        if ($this->user == $info) {
            return $this;
        }

        $uri = clone $this;
        $uri->user = $info;

        return $uri;
    }

    /**
     * Return an instance with the specified host.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return static A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host): self
    {
        if ($this->host == $host) {
            return $this;
        }

        if (!is_string($host)) {
            throw new \InvalidArgumentException('Host is not valid.');
        }

        $uri = clone $this;
        $uri->host = $host;

        return $uri;
    }

    /**
     * Return an instance with the specified port.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return static A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port): self
    {
        if ($this->port == $port) {
            return $this;
        }

        if ($port !== null && $port < 1 && $port > 65535) {
            throw new \InvalidArgumentException('Port must be null or an integer between 1 and 65535.');
        }

        $uri = clone $this;
        $uri->port = $port;

        return $uri;
    }

    /**
     * Return an instance with the specified path.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return static A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        if ($this->path == $path) {
            return $this;
        }

        if (!is_string($path)) {
            throw new \InvalidArgumentException('Uri path must be a string.');
        }

        $uri = clone $this;
        $uri->path = $path;

        return $uri;
    }

    /**
     * Return an instance with the specified query string.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return static A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query): self
    {
        if ($this->query == $query) {
            return $this;
        }

        if (!is_string($query)) {
            throw new \InvalidArgumentException('Uri query must be a string');
        }

        $query = ltrim(trim($query), '?');
        if (strlen($query) && !filter_var('http://example.com?'.$query, FILTER_VALIDATE_URL,FILTER_FLAG_QUERY_REQUIRED)) {
            throw new \InvalidArgumentException('Uri query is not valid.');
        }

        $uri = clone $this;
        $uri->query = $query;

        return $uri;
    }

    /**
     * Return an instance with the specified URI fragment.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return static A new instance with the specified fragment.
     */
    public function withFragment($fragment): self
    {
        if ($this->query == $fragment) {
            return $this;
        }

        if (!is_string($fragment)) {
            return $this;
        }

        $uri = clone $this;
        $uri->fragment = $fragment;

        return $uri;
    }

    /**
     * Return the string representation as a URI reference.
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString(): string
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $path = $this->getPath();
        $query = $this->getQuery();
        $fragment = $this->getFragment();

        if (strlen($path) && strlen($authority)) {
            if ($path[0] != '/') {
                $path = '/'.$path;
            } elseif ($path[0].($path[1] ?? '') == '//') {
                $path = '/'.ltrim($path, '/');
            }
        }

        return sprintf(
            '%s%s%s%s%s',
            $scheme ? ($scheme.':') : '',
            $authority ? ('//'.$authority) : '',
            $path,
            $query ? ('?'.$query) : '',
            $fragment ? ('#'.$fragment) : ''
        );
    }
}
