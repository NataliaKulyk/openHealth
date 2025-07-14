<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLicensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique()->nullable();
            $table->unsignedBigInteger('legal_entity_id');
            $table->string('type');
            $table->string('is_active')->nullable();
            $table->string('issued_by');
            $table->date('issued_date');
            $table->string('issuer_status')->nullable();
            $table->date('active_from_date');
            $table->string('order_no');
            $table->string('license_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('what_licensed');
            $table->boolean('is_primary')->default(false);
            $table->date('ehealth_inserted_at')->nullable();
            $table->string('ehealth_inserted_by')->nullable();
            $table->date('ehealth_updated_at')->nullable();
            $table->string('ehealth_updated_by')->nullable();
            $table->timestamps();

            $table->foreign('legal_entity_id')->references('id')->on('legal_entities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('licenses');
    }
}
