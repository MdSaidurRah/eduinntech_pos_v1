<?php

use App\Classes\LangTrans;
use App\Classes\LangTransBn;
use App\Models\Lang;
use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

class AddBengaliLanguage extends Migration
{
    public function up()
    {
        $bnLang = Lang::firstOrCreate(
            ['key' => 'bn'],
            ['name' => 'বাংলা', 'enabled' => 1]
        );

        if ($bnLang->name !== 'বাংলা' || !$bnLang->enabled) {
            $bnLang->name = 'বাংলা';
            $bnLang->enabled = 1;
            $bnLang->save();
        }

        $translations = LangTransBn::all();

        foreach ($translations as $group => $items) {
            foreach ($items as $key => $value) {
                Translation::updateOrCreate(
                    [
                        'lang_id' => $bnLang->id,
                        'group' => $group,
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }

        LangTrans::seedMainTranslations();
    }

    public function down()
    {
        $bnLang = Lang::where('key', 'bn')->first();

        if ($bnLang) {
            Translation::where('lang_id', $bnLang->id)->delete();
            $bnLang->delete();
        }
    }
}
