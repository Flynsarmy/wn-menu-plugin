<?php

namespace Flynsarmy\Menu\Updates;

use Winter\Storm\Database\Updates\Migration;
use Schema;

class AddMenuitemDataField extends Migration
{
    public function up()
    {
        Schema::table('flynsarmy_menu_menuitems', function ($table) {
            $table->text('data')->nullable();
        });
    }

    public function down()
    {
        Schema::table('flynsarmy_menu_menuitems', function ($table) {
            $table->dropColumn('data');
        });
    }
}
