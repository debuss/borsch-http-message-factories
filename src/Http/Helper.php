<?php
/**
 * @author Alexandre DEBUSSCHERE <alexandre@common-services.com>
 */
 
namespace Borsch\Http;

class Helper
{

    /**
     * Parse uploaded file from $_FILES.
     *
     * @return UploadedFile[]|UploadedFile[][]
     */
    public static function getUploadedFilesFromGlobal(): array
    {
        $uploaded_files = [];

        foreach ($_FILES as $key => $file) {
            $uploaded_files[$key] = static::getUploadedFileLeaves($file);
        }

        return $uploaded_files;
    }

    /**
     * Parse uploaded file.
     *
     * @param array $uploaded_files The non-normalized tree of uploaded file data.
     * @return UploadedFile[]|UploadedFile An array or a single instance of UploadedFile.
     */
    private static function getUploadedFileLeaves(array $uploaded_files)
    {
        $new_file = [];

        if (isset($uploaded_files['tmp_name']) && !is_array($uploaded_files['tmp_name'])) {
            $new_file = new UploadedFile(
                new Stream(fopen($uploaded_files['tmp_name'], 'r')),
                $uploaded_files['size'],
                $uploaded_files['error'],
                $uploaded_files['name'],
                $uploaded_files['type']
            );
        } elseif (isset($uploaded_files['tmp_name'][0])) {
            foreach ($uploaded_files['tmp_name'] as $key => $value) {
                $new_file[$key] = new UploadedFile(
                    new Stream(fopen($uploaded_files['tmp_name'][$key], 'r')),
                    $uploaded_files['size'][$key],
                    $uploaded_files['error'][$key],
                    $uploaded_files['name'][$key],
                    $uploaded_files['type'][$key]
                );
            }
        } else {
            foreach (array_keys($uploaded_files['tmp_name']) as $index) {
                $new_array = array_combine(
                    array_keys($uploaded_files),
                    array_column($uploaded_files, $index)
                );

                $new_file[$index] = static::getUploadedFileLeaves($new_array);
            }
        }

        return $new_file;
    }
}
