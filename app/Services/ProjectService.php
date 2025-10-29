<?php

namespace App\Services;

use App\Imports\ProjectStoreImport;
use App\Imports\ProjectUpdateImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\HeadingRowImport;

class ProjectService
{
    protected const IMPORT_TYPE_INSERT = 'INSERT';
    protected const IMPORT_TYPE_UPDATE = 'UPDATE';

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Verify the import file
     * 
     * @param string $type
     * @param \Illuminate\Http\UploadedFile $file
     * @return array $data
     */
    public function verifyImport(string $type, UploadedFile $file)
    {
        if ($type === self::IMPORT_TYPE_INSERT) {
            return $this->verifyStoreImport($file);
        } else if ($type === self::IMPORT_TYPE_UPDATE) {
            return $this->verifyUpdateImport($file);
        }
    }

    /**
     * Verify the store import
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return Array $data
     */
    protected function verifyStoreImport(UploadedFile $file)
    {
        $import = new ProjectStoreImport();

        $header = $import->getHeader();
        $data = $import->toArray($file);
        $data = $import->getValidatedData($data[0]);

        return [
            'header' => $header,
            'data' => $data,
        ];
    }

    /**
     * Verify the update import
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return Array $data
     */
    protected function verifyUpdateImport(UploadedFile $file)
    {
        $import = new ProjectUpdateImport();

        $default_header = $import->getHeader();
        $headings = (new HeadingRowImport())->toArray($file)[0][0];
        $headings = in_array('uuid', $headings) ? $headings : array_unshift($headings, 'uuid');

        $header = array_values(array_filter($headings, fn($h) => in_array($h, $default_header)));

        $data = $import->toArray($file);
        $data = $import->getValidatedData($data[0]);

        return [
            'header' => $header,
            'data' => $data,
        ];
    }
}
