<?php

use App\Classes\LangTrans;
use Illuminate\Database\Migrations\Migration;

class SeedPosFeatureTranslations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        LangTrans::seedMainTranslations();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
