<div class="modal fade" id="modal-greensand" tabindex="-1" role="dialog" aria-labelledby="gsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <form id="gsForm" class="modal-content" autocomplete="off">
            @csrf
            <input type="hidden" name="id" id="gs_id">
            <input type="hidden" name="form_mode" id="gs_mode" value="create">

            <div class="modal-header">
                <h5 class="modal-title" id="gsModalLabel"><span id="gsModalMode">Add</span> Green Sand</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div id="gsFormAlert" class="alert alert-danger d-none mb-3"></div>

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label mb-1 d-block">MM</label>
                                <div class="btn-group btn-group-sm d-flex" data-toggle="buttons" id="mm_group">
                                    <label class="btn btn-outline-secondary w-100" id="mm1_btn">
                                        <input type="radio" name="mm" value="1" class="d-none"> 1
                                    </label>
                                    <label class="btn btn-outline-secondary w-100" id="mm2_btn">
                                        <input type="radio" name="mm" value="2" class="d-none"> 2
                                    </label>
                                </div>
                                <div id="mm_error" class="invalid-feedback d-block" style="display:none;"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label mb-1">Mix Ke</label>
                                <input type="text" name="mix_ke" id="mix_ke" class="form-control" placeholder="Enter Mix Ke">
                                <div id="mix_ke_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label mb-1">Mix Start</label>
                                <input type="time" name="mix_start" id="mix_start" class="form-control">
                                <div id="mix_start_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label mb-1">Mix Finish</label>
                                <input type="time" name="mix_finish" id="mix_finish" class="form-control">
                                <div id="mix_finish_error" class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-mm" role="tab"><i class="ri-flask-line mr-1"></i> MM Sample</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-additive" role="tab"><i class="ri-pie-chart-2-line mr-1"></i> Additive</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-bc" role="tab"><i class="ri-alert-line mr-1"></i> BC Sample</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-return" role="tab"><i class="ri-recycle-line mr-1"></i> Return Sand</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-moulding" role="tab"><i class="ri-hammer-line mr-1"></i> Data Moulding</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-mm" role="tabpanel">
                        <div class="row">
                            @php
                                $mmFields = [
                                    ['mm_p', 'P'],
                                    ['mm_c', 'C'],
                                    ['mm_gt', 'G.T'],
                                    ['mm_cb_mm', 'CB MM'],
                                    ['mm_cb_lab', 'CB Lab'],
                                    ['mm_m', 'Moisture'],
                                    ['machine_no', 'Nomor Mesin'],
                                    ['mm_bakunetsu', 'Bakunetsu'],
                                    ['mm_ac', 'AC'],
                                    ['mm_tc', 'TC'],
                                    ['mm_vsd', 'VSD'],
                                    ['mm_ig', 'IG'],
                                    ['mm_cb_weight', 'CB Weight'],
                                    ['mm_tp50_weight', 'TP 50 Weight'],
                                    ['mm_tp50_height', 'TP 50 Height'],
                                    ['mm_ssi', 'SSI'],
                                ];
                            @endphp
                            @foreach($mmFields as [$name, $label])
                                <div class="col-md-3 mb-3">
                                    <label class="mb-1">{{ $label }}</label>
                                    @if($name === 'machine_no')
                                        <input type="text" name="machine_no" id="machine_no" class="form-control" placeholder="Nomor Mesin">
                                    @else
                                        <input type="text" inputmode="decimal" pattern="^-?[0-9]*[\,\.]?[0-9]+$" name="{{ $name }}" id="{{ $name }}" class="form-control" placeholder="Enter Sample {{ $label }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-additive" role="tabpanel">
                        <div class="row">
                            @foreach(['add_m3' => 'M3', 'add_vsd' => 'VSD', 'add_sc' => 'SC'] as $name => $label)
                                <div class="col-md-4 mb-3">
                                    <label class="mb-1">{{ $label }}</label>
                                    <input type="text" inputmode="decimal" pattern="^-?[0-9]*[\,\.]?[0-9]+$" name="{{ $name }}" id="{{ $name }}" class="form-control" placeholder="Enter Sample {{ $label }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-bc" role="tabpanel">
                        <div class="row">
                            @foreach(['bc12_cb' => 'BC 12 (CB)', 'bc11_ac' => 'BC 11 (AC)', 'bc16_cb' => 'BC 16 (CB)', 'bc12_m' => 'BC 12 (M)', 'bc11_vsd' => 'BC 11 (VSD)', 'bc16_m' => 'BC 16 (M)'] as $name => $label)
                                <div class="col-md-4 mb-3">
                                    <label class="mb-1">{{ $label }}</label>
                                    <input type="text" inputmode="decimal" pattern="^-?[0-9]*[\,\.]?[0-9]+$" name="{{ $name }}" id="{{ $name }}" class="form-control" placeholder="Enter Sample {{ $label }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-return" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="mb-1">Time</label>
                                <input type="time" name="rs_time" id="rs_time" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="mb-1">Type</label>
                                <input type="text" name="rs_type" id="rs_type" class="form-control" placeholder="Type (WIP/ES01/...)">
                            </div>
                        </div>
                        <div class="row">
                            @foreach(['bc9_moist' => 'BC 9 Moist', 'bc10_moist' => 'BC 10 Moist', 'bc11_moist' => 'BC 11 Moist', 'bc9_temp' => 'BC 9 Temp', 'bc10_temp' => 'BC 10 Temp', 'bc11_temp' => 'BC 11 Temp'] as $name => $label)
                                <div class="col-md-4 mb-3">
                                    <label class="mb-1">{{ $label }}</label>
                                    <input type="text" inputmode="decimal" pattern="^-?[0-9]*[\,\.]?[0-9]+$" name="{{ $name }}" id="{{ $name }}" class="form-control" placeholder="Enter {{ $label }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-moulding" role="tabpanel">
                        <div class="row">
                            @foreach([
                                    'add_water_mm' => 'Add Water MM1',
                                    'add_water_mm_2' => 'Add Water MM2',
                                    'temp_sand_mm_1' => 'Temp Sand MM1',
                                    'rcs_pick_up' => 'RCS Pick Up',
                                    'total_flask' => 'Total Flask',
                                    'rcs_avg' => 'RCS Avg',
                                    'add_bentonite_ma' => 'Add Bentonite MA',
                                    'total_sand' => 'Total Sand',
                                    'add_water_bc10' => 'Add Water BC10',
                                    'lama_bc10_jalan' => 'Lama BC10 Jalan (menit)',
                                    'rating_pasir_es' => 'Rating Pasir Es'
                                ] as $name => $label)
                                    <div class="col-md-3 mb-3">
                                        <label class="mb-1">{{ $label }}</label>
                                        <input type="text" inputmode="decimal" pattern="^-?[0-9]*[\,\.]?[0-9]+$" name="{{ $name }}" id="{{ $name }}" class="form-control" placeholder="Masukkan {{ $label }}">
                                    </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary mr-2" data-dismiss="modal"><i class="ri-close-line me-1"></i> Cancel</button>
                <button type="submit" class="btn btn-success" id="gsSubmitBtn"><i class="ri-checkbox-circle-line me-1"></i> Submit</button>
            </div>
        </form>
    </div>
</div>
