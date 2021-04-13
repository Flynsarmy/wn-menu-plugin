<?php

namespace Flynsarmy\Menu\Updates;

use Winter\Storm\Database\Updates\Migration;
use Schema;

class AddMenuitemTargetField extends Migration
{
    public function up()
    {
        Schema::table('flynsarmy_menu_menuitems', function ($table) {
            $table->string('target_attrib')->default('');
        });
    }

    public function down()
    {
        Schema::table('flynsarmy_menu_menuitems', function ($table) {
            $table->dropColumn('target_attrib');
        });
    }
}
