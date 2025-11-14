<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FileTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fileTypes = [
            // PDF
            ['mime_type' => 'application/pdf', 'file_extension' => 'pdf', 'is_supported' => true],

            // Microsoft Office - Word
            ['mime_type' => 'application/msword', 'file_extension' => 'doc', 'is_supported' => true],
            ['mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'file_extension' => 'docx', 'is_supported' => true],

            // Microsoft Office - Excel
            ['mime_type' => 'application/vnd.ms-excel', 'file_extension' => 'xls', 'is_supported' => true],
            ['mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'file_extension' => 'xlsx', 'is_supported' => true],

            // Microsoft Office - PowerPoint
            ['mime_type' => 'application/vnd.ms-powerpoint', 'file_extension' => 'ppt', 'is_supported' => true],
            ['mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'file_extension' => 'pptx', 'is_supported' => true],

            // Images
            ['mime_type' => 'image/jpeg', 'file_extension' => 'jpg', 'is_supported' => true],
            ['mime_type' => 'image/jpeg', 'file_extension' => 'jpeg', 'is_supported' => true],
            ['mime_type' => 'image/png', 'file_extension' => 'png', 'is_supported' => true],
            ['mime_type' => 'image/gif', 'file_extension' => 'gif', 'is_supported' => true],
            ['mime_type' => 'image/bmp', 'file_extension' => 'bmp', 'is_supported' => true],
            ['mime_type' => 'image/tiff', 'file_extension' => 'tiff', 'is_supported' => true],

            // Text
            ['mime_type' => 'text/plain', 'file_extension' => 'txt', 'is_supported' => true],
            ['mime_type' => 'text/html', 'file_extension' => 'html', 'is_supported' => true],
            ['mime_type' => 'text/csv', 'file_extension' => 'csv', 'is_supported' => true],

            // Rich Text
            ['mime_type' => 'application/rtf', 'file_extension' => 'rtf', 'is_supported' => true],

            // OpenDocument
            ['mime_type' => 'application/vnd.oasis.opendocument.text', 'file_extension' => 'odt', 'is_supported' => true],

            // Unsupported (examples)
            ['mime_type' => 'application/x-executable', 'file_extension' => 'exe', 'is_supported' => false],
            ['mime_type' => 'application/x-msdownload', 'file_extension' => 'dll', 'is_supported' => false],
            ['mime_type' => 'application/x-sh', 'file_extension' => 'sh', 'is_supported' => false],
        ];

        foreach ($fileTypes as $fileType) {
            \DB::table('file_types')->insert(array_merge($fileType, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
