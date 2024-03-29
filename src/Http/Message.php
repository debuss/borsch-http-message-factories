<?php
/**
 * @author Alexandre DEBUSSCHERE <alexandre@common-services.com>
 */
 
namespace Borsch\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Message
 *
 * @package Borsch
 */
class Message implements MessageInterface
{

    /** @var string */
    protected $protocol_version = '1.1';

    /** @var UriInterface */
    protected $uri;

    /** @var array */
    protected $headers = [
        'Content-Type' => ['text/html; charset=utf-8']
    ];

    /** @var StreamInterface */
    protected $stream;

    /** @var array */
    protected $allowed_protocol_versions = ['1.0', '1.1', '2.0', '2'];

    /**
     * Message constructor.
     *
     * @param StreamInterface|null $stream
     */
    public function __construct(StreamInterface $stream = null)
    {
        $this->stream = $stream ?: new Stream(fopen('php://temp', 'r+'));
    }

    /**
     * Retrieves the HTTP protocol version as a string.
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol_version;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version): self
    {
        if ($this->protocol_version == (string)$version || !in_array((string)$version, $this->allowed_protocol_versions, true)) {
            return $this;
        }

        $message = clone $this;
        $message->protocol_version = (string)$version;

        return $message;
    }

    /**
     * Retrieves all message header values.
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name): bool
    {
        return in_array(
            strtolower((string)$name),
            array_map('strtolower', array_keys($this->headers))
        );
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name): array
    {
        $name = strtolower((string)$name);

        if (!$this->hasHeader($name)) {
            return [];
        }

        foreach ($this->headers as $header_name => $value) {
            if ($name == strtolower($header_name)) {
                return $this->headers[$header_name];
            }
        }

        return [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader((string)$name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value): self
    {
        if (!$this->isValidHeaderName($name)) {
            throw new \InvalidArgumentException('Header name must be an RFC 7230 compatible string.');
        }

        if (!$this->isValidHeaderValue($value)) {
            throw new \InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
        }

        $message = clone $this;

        if ($message->hasHeader((string)$name)) {
            array_filter($message->headers, function ($header_name) use ($name) {
                return strtolower((string)$name) != strtolower($header_name);
            }, ARRAY_FILTER_USE_KEY);
        }

        $message->headers[(string)$name] = array_map('trim', (array)$value);

        return $message;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value): self
    {
        if (!$this->isValidHeaderName($name)) {
            throw new \InvalidArgumentException('Header name must be an RFC 7230 compatible string.');
        }

        if (!$this->isValidHeaderValue($value)) {
            throw new \InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
        }

        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $message = clone $this;

        foreach (array_keys($message->headers) as $header_name) {
            if ($name == strtolower($header_name)) {
                $message->headers[$header_name][] = $value;
                break;
            }
        }

        return $message;
    }

    /**
     * Return an instance without the specified header.
     * Header resolution MUST be done without case-sensitivity.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name): self
    {
        $name = strtolower((string)$name);

        $message = clone $this;

        $message->headers = array_filter($message->headers, function ($header_name) use ($name) {
            return $name != strtolower($header_name);
        }, ARRAY_FILTER_USE_KEY);

        return $message;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * Return an instance with the specified message body.
     * The body MUST be a StreamInterface object.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body): self
    {
        if ($body === $this->stream) {
            return $this;
        }

        $message = clone $this;
        $message->stream = $body;

        return $message;
    }

    /**
     * @param string $name
     * @return bool
     * @see https://github.com/zendframework/zend-diactoros/blob/master/src/HeaderSecurity.php
     */
    private function isValidHeaderName($name): bool
    {
        if (!is_string($name) || !strlen($name) || !preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $values
     * @return bool
     * @see https://github.com/zendframework/zend-diactoros/blob/master/src/HeaderSecurity.php
     */
    private function isValidHeaderValue($values): bool
    {
        foreach ((array)$values as $value) {
            if (!is_string($value) && !is_numeric($value) ||
                preg_match("/(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))/", $value) ||
                preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $value)) {
                return false;
            }
        }

        return true;
    }
}
