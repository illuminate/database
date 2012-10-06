<?php

use Illuminate\Database\Console\Migrations\Migration;

class {{class}} extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('{{table}}', function($table)
		{
			//
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('{{table}}', function($table)
		{
			//
		});
	}

}