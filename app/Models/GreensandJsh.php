<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GreensandJsh extends Model
{
    protected $table = 'tb_greensand_jsh';

    protected $fillable = [
        'date',
        'shift',
        'mm',
        'mix_ke',
        'mix_start',
        'mix_finish',

        // MM Sample
        'mm_p',
        'mm_c',
        'mm_gt',
        'mm_cb_mm',
        'mm_cb_lab',
        'mm_m',
        'machine_no',
        'mm_bakunetsu',
        'mm_ac',
        'mm_tc',
        'mm_vsd',
        'mm_ig',
        'mm_cb_weight',
        'mm_tp50_weight',
        'mm_tp50_height',
        'mm_ssi',

        // Additive
        'add_m3',
        'add_vsd',
        'add_sc',

        // BC Sample
        'bc12_cb',
        'bc12_m',
        'bc11_ac',
        'bc11_vsd',
        'bc16_cb',
        'bc16_m',

        // Return Sand
        'rs_time',
        'rs_type',
        'bc9_moist',
        'bc10_moist',
        'bc11_moist',
        'bc9_temp',
        'bc10_temp',
        'bc11_temp',

        // Moulding Data
        'add_water_mm',
        'add_water_mm_2',
        'temp_sand_mm_1',
        'rcs_pick_up',
        'total_flask',
        'rcs_avg',
        'add_bentonite_ma',
        'total_sand',
        'add_water_bc10',     
        'lama_bc10_jalan',    
        'rating_pasir_es',    
    ];

    protected $casts = [
        'date' => 'datetime',
        'mix_ke' => 'integer',
        'mix_start' => 'string',
        'mix_finish' => 'string',
        'rs_time' => 'string',
        'machine_no' => 'string',
        'add_water_bc10' => 'integer',
        'lama_bc10_jalan' => 'integer',
        'rating_pasir_es' => 'string',
    ];
}
