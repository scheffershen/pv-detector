<?php

namespace App\Manager\RevueManagement;

class ZipManager
{

    public function extractArchive(string $pathToExtract, string $pathToArchive): array
    {
        $zip = new \ZipArchive();

        if ($statusCode = (true !== $zip->open($pathToArchive))) {
            throw new \Exception($this->zipStatusString($statusCode), $statusCode);
        }

        if (!$zip->extractTo($pathToExtract)) {
            throw new \Exception('Error during extracting data from archive');
        }
        $zip->close();

		$images = [];

		$all_files = glob($pathToExtract . "/*.*");
	    for ($i=0; $i < count($all_files); $i++)
	    {
	      $image_name = $all_files[$i];
	      $supported_format = array('gif','jpg','jpeg','png');
	      $ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
	      if (in_array($ext, $supported_format))
	      {
	           $images[] = basename($image_name);
	      } else {
	        continue;
	      }
	    }

        return $images;
    }

    private function zipStatusString(int $status): string
    {
        switch ($status) {
            case ZipArchive::ER_MULTIDISK: return 'Multi-disk zip archives not supported';
            case ZipArchive::ER_RENAME: return 'Renaming temporary file failed';
            case ZipArchive::ER_CLOSE: return 'Closing zip archive failed';
            case ZipArchive::ER_SEEK: return 'Seek error';
            case ZipArchive::ER_READ: return 'Read error';
            case ZipArchive::ER_WRITE: return 'Write error';
            case ZipArchive::ER_CRC: return 'CRC error';
            case ZipArchive::ER_ZIPCLOSED: return 'Containing zip archive was closed';
            case ZipArchive::ER_NOENT: return 'No such file';
            case ZipArchive::ER_EXISTS: return 'File already exists';
            case ZipArchive::ER_OPEN: return 'Can\'t open file';
            case ZipArchive::ER_TMPOPEN: return 'Failure to create temporary file';
            case ZipArchive::ER_ZLIB: return 'Zlib error';
            case ZipArchive::ER_MEMORY: return 'Malloc failure';
            case ZipArchive::ER_CHANGED: return 'Entry has been changed';
            case ZipArchive::ER_COMPNOTSUPP: return 'Compression method not supported';
            case ZipArchive::ER_EOF: return 'Premature EOF';
            case ZipArchive::ER_INVAL: return 'Invalid argument';
            case ZipArchive::ER_NOZIP: return 'Not a zip archive';
            case ZipArchive::ER_INTERNAL: return 'Internal error';
            case ZipArchive::ER_INCONS: return 'Zip archive inconsistent';
            case ZipArchive::ER_REMOVE: return 'Can\'t remove file';
            case ZipArchive::ER_DELETED: return 'Entry has been deleted';
            default: return sprintf('Unknown status %s', $status);
        }
    }
}