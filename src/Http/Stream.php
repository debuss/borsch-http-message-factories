<?php
/**
 * @author Alexandre DEBUSSCHERE <alexandre@common-services.com>
 */
 
namespace Borsch\Http;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{

    /** @var resource */
    protected $stream;

    /** @var array */
    protected $meta_data;

    /**
     * Stream constructor.
     *
     * @param string|resource $stream
     * @param string $mode
     * @throws \InvalidArgumentException
     */
    public function __construct($stream, string $mode = 'r+')
    {
        if (!is_string($stream) && !is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a string or resource.');
        }

        if (is_resource($stream)) {
            $this->stream = $stream;
        } else {
            $this->stream = fopen('php://temp', $mode);
            $this->stream->write($stream);
        }
    }

    /**
     * Stream destructor.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     * Warning: This could attempt to load a large amount of data into memory.
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString(): string
    {
        try {
            if ($this->isReadable() && $this->isSeekable()) {
                $this->rewind();

                return $this->getContents();
            }
        } finally {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->detach();
    }

    /**
     * Separates any underlying resources from the stream.
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;

        return $stream;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (!$this->stream) {
            return null;
        }

        return fstat($this->stream)['size'] ?? null;
    }

    /**
     * Returns the current position of the file read/write pointer.
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell(): int
    {
        $position = ftell($this->stream);
        if ($position === false) {
            throw new \RuntimeException('Unable to get the current position of the file read/write pointer.');
        }

        return $position;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return is_resource($this->stream) && feof($this->stream);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        return (bool)$this->getMetadata('seekable');
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable() || fseek($this->stream, (int)$offset, (int)$whence) == -1) {
            throw new \RuntimeException('Could not seek in stream.');
        }
    }

    /**
     * Seek to the beginning of the stream.
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @throws \RuntimeException on failure.
     * @link http://www.php.net/manual/en/function.fseek.php
     * @see seek()
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return in_array(
            $this->getMetadata('mode'),
            ['a', 'a+', 'c', 'c+', 'r+', 'w', 'w+', 'w+b', 'x', 'x+']
        );
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string): int
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable.');
        }

        $bytes = fwrite($this->stream, (string)$string);
        if ($bytes === false) {
            throw new \RuntimeException('Could not write to the stream although it is writable.');
        }

        return $bytes;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        return in_array(
            $this->getMetadata('mode'),
            ['a+', 'c+', 'r', 'r+', 'w+', 'w+b', 'x+']
        );
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length): string
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable.');
        }

        $string = fread($this->stream, (int)$length);
        if ($string === false) {
            throw new \RuntimeException('Could not read the stream although it is readable.');
        }

        return $string;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new \RuntimeException(
                'Could not get content from the stream although it is readable.'
            );
        }

        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if (!is_array($this->meta_data) || !count($this->meta_data)) {
            $this->meta_data = is_resource($this->stream) ? stream_get_meta_data($this->stream) : [];
        }

        if (!$key) {
            return $this->meta_data;
        }

        return $this->meta_data[$key] ?? null;
    }
}
