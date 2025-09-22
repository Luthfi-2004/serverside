<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AceLine extends Model
{
    protected $table = 'tb_greensand_ace';

    protected $fillable = [
        'date','shift','product_type_id','product_type_name','number','no_mix',
        'sample_start','sample_finish',
        'p','c','gt','cb_lab','moisture',
        'machine_no','bakunetsu','ac','tc','vsd','ig',
        'cb_weight','tp50_weight','ssi',
        'dw29_vas','dw29_debu',
        'dw31_vas','dw31_id','dw31_moldex','dw31_sc',
        'bc13_cb','bc13_c','bc13_m',
    ];

    protected $casts = [
        'date'         => 'datetime',
        // time fields biarkan string supaya fleksibel (H:i)
        'sample_start' => 'string',
        'sample_finish'=> 'string',

        // numeric casts (biar enak di-format)
        'p' => 'float','c' => 'float','gt' => 'float','cb_lab' => 'float','moisture' => 'float',
        'bakunetsu' => 'float','ac' => 'float','tc' => 'float','vsd' => 'float','ig' => 'float',
        'cb_weight' => 'float','tp50_weight' => 'float','ssi' => 'float',
        'dw29_vas' => 'float','dw29_debu' => 'float',
        'dw31_vas' => 'float','dw31_id' => 'float','dw31_moldex' => 'float','dw31_sc' => 'float',
        'bc13_cb' => 'float','bc13_c' => 'float','bc13_m' => 'float',
        'number' => 'integer','no_mix' => 'integer','product_type_id' => 'integer',
    ];

    /* ===== Scopes ringan buat index/filter ===== */
    public function scopeFilterDate($q, ?string $ymd)
    {
        if ($ymd) $q->whereDate('date', $ymd);
        return $q;
    }
    public function scopeFilterShift($q, ?string $shift)
    {
        if ($shift) $q->where('shift', $shift);
        return $q;
    }
    public function scopeFilterProduct($q, $productTypeId)
    {
        if ($productTypeId) $q->where('product_type_id', $productTypeId);
        return $q;
    }
}
