<?php
/**
 * @author Alexandre DEBUSSCHERE <alexandre@common-services.com>
 */
 
namespace Borsch\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{

    /** @var StreamInterface */
    protected $stream;

    /** @var string */
    protected $name;

    /** @var string */
    protected $type;

    /** @var string */
    protected $tmp_name;

    /** @var int */
    protected $error;

    /** @var int */
    protected $size;

    /** @var bool */
    protected $has_been_moved = false;

    public function __construct(StreamInterface $stream, int $size = null, int $error = UPLOAD_ERR_OK, string $client_filename = null, string $client_media_type = null)
    {
        $this->stream = $stream;
        $this->tmp_name = $stream->getMetadata('uri');
        $this->size = $size ?: $stream->getSize();
        $this->error = $error;
        $this->name = $client_filename;
        $this->type = $client_media_type;
    }

    /**
     * Retrieve a stream representing the uploaded file.
     * This method MUST return a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     * If the moveTo() method has been called previously, this method MUST raise
     * an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream(): StreamInterface
    {
        if ($this->error != UPLOAD_ERR_OK) {
            throw new \RuntimeException('Uploaded file can not be retrieved due to upload error.');
        }

        if ($this->has_been_moved) {
            throw new \RuntimeException('Uploaded file has already been moved.');
        }

        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * Use this method as an alternative to move_uploaded_file(). This method is
     * guaranteed to work in both SAPI and non-SAPI environments.
     * Implementations must determine which environment they are in, and use the
     * appropriate method (move_uploaded_file(), rename(), or a stream
     * operation) to perform the operation.
     *
     * $targetPath may be an absolute path, or a relative path. If it is a
     * relative path, resolution should be the same as used by PHP's rename()
     * function.
     *
     * The original file or stream MUST be removed on completion.
     *
     * If this method is called more than once, any subsequent calls MUST raise
     * an exception.
     *
     * When used in an SAPI environment where $_FILES is populated, when writing
     * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
     * used to ensure permissions and upload status are verified correctly.
     *
     * If you wish to move to a stream, use getStream(), as SAPI operations
     * cannot guarantee writing to stream destinations.
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $target_path Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $target_path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($target_path)
    {
        if (!is_string($target_path) || !strlen($target_path)) {
            throw new \RuntimeException('Target path is invalid.');
        }

        if ($this->has_been_moved) {
            throw new \RuntimeException('Uploaded file has already been moved.');
        }

        $source = $this->getStream();
        if ($source->isSeekable()) {
            $source->rewind();
        }

        $destination = new Stream($target_path, 'w');

        $chunk_size = PHP_INT_SIZE * 1024;

        while (!$source->eof()) {
            if (!$destination->write($source->read($chunk_size))) {
                break;
            }
        }

        $this->has_been_moved = true;
    }

    /**
     * Retrieve the file size.
     * Implementations SHOULD return the value stored in the "size" key of
     * the file in the $_FILES array if available, as PHP calculates this based
     * on the actual size transmitted.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     * If the file was uploaded successfully, this method MUST return
     * UPLOAD_ERR_OK.
     * Implementations SHOULD return the value stored in the "error" key of
     * the file in the $_FILES array.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     * Implementations SHOULD return the value stored in the "name" key of
     * the file in the $_FILES array.
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    /**
     * Retrieve the media type sent by the client.
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     * Implementations SHOULD return the value stored in the "type" key of
     * the file in the $_FILES array.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType(): ?string
    {
        return $this->type;
    }
}
