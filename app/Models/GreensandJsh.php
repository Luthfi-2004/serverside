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
        'mm_p',
        'mm_c',
        'mm_gt',
        'mm_cb_mm',
        'mm_cb_lab',
        'mm_m',
        'mm_bakunetsu',
        'mm_ac',
        'mm_tc',
        'mm_vsd',
        'mm_ig',
        'mm_cb_weight',
        'mm_tp50_weight',
        'mm_ssi',
        'add_m3',
        'add_vsd',
        'add_sc',
        'bc12_cb',
        'bc12_m',
        'bc11_ac',
        'bc11_vsd',
        'bc16_cb',
        'bc16_m',
        'rs_time',
        'rs_type',
        'bc9_moist',
        'bc10_moist',
        'bc11_moist',
        'bc9_temp',
        'bc10_temp',
        'bc11_temp',
    ];

    protected $casts = [
        'date' => 'datetime',
        'mix_start' => 'datetime:H:i',
        'mix_finish' => 'datetime:H:i',
        'rs_time' => 'datetime:H:i',
    ];
}
