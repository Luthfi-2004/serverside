<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tb_greensand_ace', function (Blueprint $table) {
            $table->id();

            // Meta waktu & shift (ngikut pola JSH)
            $table->timestamp('date')->nullable()->comment('Tanggal proses / lot date');
            $table->enum('shift', ['D', 'S', 'N'])->nullable();

            // Product type (nanti bisa dihubungkan ke tabel master)
            $table->foreignId('product_type_id')->nullable()->index()
                  ->comment('Relasi ke master product type (optional, constraint bisa ditambah belakangan)');
            $table->string('product_type_name', 100)->nullable()
                  ->comment('Snapshot nama product type (kalau perlu simpan teksnya)');

            // Nomor / urutan / identitas baris
            $table->integer('number')->nullable()->comment('Nomor urut / nomor sample');
            $table->integer('no_mix')->nullable()->comment('NO mix');

            // Sample taking time
            $table->time('sample_start')->nullable()->comment('Sample taking time start');
            $table->time('sample_finish')->nullable()->comment('Sample taking time finish');

            // Parameter utama (pakai decimal(8,2) biar konsisten dengan modul lain)
            $table->decimal('p', 8, 2)->nullable()->comment('P');
            $table->decimal('c', 8, 2)->nullable()->comment('C');
            $table->decimal('gt', 8, 2)->nullable()->comment('G.T');
            $table->decimal('cb_lab', 8, 2)->nullable()->comment('Cb Lab');
            $table->decimal('moisture', 8, 2)->nullable()->comment('Moisture');

            // Mesin & kondisi
            $table->string('machine_no', 50)->nullable()->comment('Nomor Mesin');
            $table->decimal('bakunetsu', 8, 2)->nullable()->comment('Bakunetsu');
            $table->decimal('ac', 8, 2)->nullable()->comment('AC');
            $table->decimal('tc', 8, 2)->nullable()->comment('TC');
            $table->decimal('vsd', 8, 2)->nullable()->comment('VSD');
            $table->decimal('ig', 8, 2)->nullable()->comment('IG');

            // Bobot / weight
            $table->decimal('cb_weight', 8, 2)->nullable()->comment('CB Weight');
            $table->decimal('tp50_weight', 8, 2)->nullable()->comment('TP 50 Weight');

            // Lain-lain / kualitas
            $table->decimal('ssi', 8, 2)->nullable()->comment('SSI');

            // DW29
            $table->decimal('dw29_vas', 8, 2)->nullable()->comment('DW29_VAS');
            $table->decimal('dw29_debu', 8, 2)->nullable()->comment('DW29_Debu');

            // DW31
            $table->decimal('dw31_vas', 8, 2)->nullable()->comment('DW31_VAS');
            $table->decimal('dw31_id', 8, 2)->nullable()->comment('DW31_ID');
            $table->decimal('dw31_moldex', 8, 2)->nullable()->comment('DW31_Moldex');
            $table->decimal('dw31_sc', 8, 2)->nullable()->comment('DW31_SC');

            // BC13
            $table->decimal('bc13_cb', 8, 2)->nullable()->comment('BC13_CB');
            $table->decimal('bc13_c', 8, 2)->nullable()->comment('BC13_C');
            $table->decimal('bc13_m', 8, 2)->nullable()->comment('BC13_M');

            // Indeks untuk query umum
            $table->index(['date', 'shift'], 'ace_date_shift_index');
            $table->index(['sample_start', 'sample_finish'], 'ace_sample_time_index');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_greensand_ace');
    }
};
