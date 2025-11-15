<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupportedLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['lang_code' => 'en', 'lang_name' => 'English', 'is_default' => true],
            ['lang_code' => 'es', 'lang_name' => 'Spanish', 'is_default' => false],
            ['lang_code' => 'fr', 'lang_name' => 'French', 'is_default' => false],
            ['lang_code' => 'de', 'lang_name' => 'German', 'is_default' => false],
            ['lang_code' => 'it', 'lang_name' => 'Italian', 'is_default' => false],
            ['lang_code' => 'pt', 'lang_name' => 'Portuguese', 'is_default' => false],
            ['lang_code' => 'pt-BR', 'lang_name' => 'Portuguese (Brazil)', 'is_default' => false],
            ['lang_code' => 'zh-CN', 'lang_name' => 'Chinese (Simplified)', 'is_default' => false],
            ['lang_code' => 'zh-TW', 'lang_name' => 'Chinese (Traditional)', 'is_default' => false],
            ['lang_code' => 'ja', 'lang_name' => 'Japanese', 'is_default' => false],
            ['lang_code' => 'ko', 'lang_name' => 'Korean', 'is_default' => false],
            ['lang_code' => 'ru', 'lang_name' => 'Russian', 'is_default' => false],
            ['lang_code' => 'nl', 'lang_name' => 'Dutch', 'is_default' => false],
            ['lang_code' => 'pl', 'lang_name' => 'Polish', 'is_default' => false],
            ['lang_code' => 'ar', 'lang_name' => 'Arabic', 'is_default' => false],
            ['lang_code' => 'cs', 'lang_name' => 'Czech', 'is_default' => false],
            ['lang_code' => 'da', 'lang_name' => 'Danish', 'is_default' => false],
            ['lang_code' => 'fi', 'lang_name' => 'Finnish', 'is_default' => false],
            ['lang_code' => 'sv', 'lang_name' => 'Swedish', 'is_default' => false],
            ['lang_code' => 'no', 'lang_name' => 'Norwegian', 'is_default' => false],
        ];

        foreach ($languages as $language) {
            \DB::table('supported_languages')->insert(array_merge($language, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
