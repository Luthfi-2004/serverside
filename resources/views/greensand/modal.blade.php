<div class="modal fade" id="modal-greensand" tabindex="-1" role="dialog" aria-labelledby="gsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <form id="gsForm" class="modal-content" autocomplete="off">
            @csrf
            <input type="hidden" name="id" id="gs_id">
            <input type="hidden" name="form_mode" id="gs_mode" value="create">

            <div class="modal-header">
                <h5 class="modal-title" id="gsModalLabel"><span id="gsModalMode">Add</span> Green Sand</h5>
                <button type="button" class="close" data-dismiss="modal"
                    aria-label="Close"><span>&times;</span></button>
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
                                <input type="number" min="1" step="1" name="mix_ke" id="mix_ke" class="form-control"
                                    placeholder="Enter Mix Ke">
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
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab-mm" role="tab"><i
                                class="ri-flask-line mr-1"></i> MM Sample</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-additive" role="tab"><i
                                class="ri-pie-chart-2-line mr-1"></i> Additive</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-bc" role="tab"><i
                                class="ri-alert-line mr-1"></i> BC Sample</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-return" role="tab"><i
                                class="ri-recycle-line mr-1"></i> Return Sand</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-moulding" role="tab"><i
                                class="ri-hammer-line mr-1"></i> Data Moulding</a>
                    </li>
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
                                    ['machine_no', 'Nomor Mesin'],    // <-- NEW
                                    ['mm_bakunetsu', 'Bakunetsu'],
                                    ['mm_ac', 'AC'],
                                    ['mm_tc', 'TC'],
                                    ['mm_vsd', 'Vsd'],
                                    ['mm_ig', 'IG'],
                                    ['mm_cb_weight', 'CB Weight'],
                                    ['mm_tp50_weight', 'TP 50 Weight'],
                                    ['mm_tp50_height', 'TP 50 Height'],
                                    ['mm_ssi', 'SSI'],
                                ];
                            @endphp

                            @foreach ($mmFields as [$name, $label])
                                <div class="col-md-3 mb-3">
                                    <label class="mb-1">{{ $label }}</label>
                                    @if ($name === 'machine_no')
                                        <input type="text" name="machine_no" id="machine_no" class="form-control"
                                            placeholder="Masukkan Nomor Mesin">
                                    @else
                                        <input type="number" step="any" name="{{ $name }}" id="{{ $name }}" class="form-control"
                                            placeholder="Enter Sample {{ $label }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-additive" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="mb-1">M3</label>
                                <input type="number" step="any" name="add_m3" id="add_m3" class="form-control"
                                    placeholder="Enter Sample M3">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="mb-1">VSD</label>
                                <input type="number" step="any" name="add_vsd" id="add_vsd" class="form-control"
                                    placeholder="Enter Sample VSD">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="mb-1">SC</label>
                                <input type="number" step="any" name="add_sc" id="add_sc" class="form-control"
                                    placeholder="Enter Sample SC">
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-bc" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="mb-1">BC 12 (CB)</label>
                                    <input type="number" step="any" name="bc12_cb" id="bc12_cb" class="form-control"
                                        placeholder="Enter Sample BC12 CB">
                                </div>
                                <div class="mb-3">
                                    <label class="mb-1">BC 11 (AC)</label>
                                    <input type="number" step="any" name="bc11_ac" id="bc11_ac" class="form-control"
                                        placeholder="Enter Sample BC11 AC">
                                </div>
                                <div class="mb-0">
                                    <label class="mb-1">BC 16 (CB)</label>
                                    <input type="number" step="any" name="bc16_cb" id="bc16_cb" class="form-control"
                                        placeholder="Enter Sample BC16 CB">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="mb-1">BC 12 (M)</label>
                                    <input type="number" step="any" name="bc12_m" id="bc12_m" class="form-control"
                                        placeholder="Enter Sample BC12 M">
                                </div>
                                <div class="mb-3">
                                    <label class="mb-1">BC 11 (VSD)</label>
                                    <input type="number" step="any" name="bc11_vsd" id="bc11_vsd" class="form-control"
                                        placeholder="Enter Sample BC11 VSD">
                                </div>
                                <div class="mb-0">
                                    <label class="mb-1">BC 16 (M)</label>
                                    <input type="number" step="any" name="bc16_m" id="bc16_m" class="form-control"
                                        placeholder="Enter Sample BC16 M">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-return" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="mb-1">Time</label>
                                <input type="time" name="rs_time" id="rs_time" class="form-control">
                                <div id="rs_time_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="mb-1">Type</label>
                                <input type="text" name="rs_type" id="rs_type" class="form-control"
                                    placeholder="Enter Sample Type (WIP / ES01 / ...)">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="mb-1">Moisture</label>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label mb-1">BC 9</label>
                                    <input type="number" step="any" name="bc9_moist" id="bc9_moist" class="form-control"
                                        placeholder="Enter Moisture BC 9">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label mb-1">BC 10</label>
                                    <input type="number" step="any" name="bc10_moist" id="bc10_moist"
                                        class="form-control" placeholder="Enter Moisture BC 10">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label mb-1">BC 11</label>
                                    <input type="number" step="any" name="bc11_moist" id="bc11_moist"
                                        class="form-control" placeholder="Enter Moisture BC 11">
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="mb-1">Temperature</label>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label mb-1">BC 9</label>
                                    <input type="number" step="any" name="bc9_temp" id="bc9_temp" class="form-control"
                                        placeholder="Enter Temp BC 9">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label mb-1">BC 10</label>
                                    <input type="number" step="any" name="bc10_temp" id="bc10_temp" class="form-control"
                                        placeholder="Enter Temp BC 10">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label mb-1">BC 11</label>
                                    <input type="number" step="any" name="bc11_temp" id="bc11_temp" class="form-control"
                                        placeholder="Enter Temp BC 11">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-moulding" role="tabpanel">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="mb-1">Add Water MM 1</label>
                                <input type="number" step="1" min="0" name="add_water_mm" id="add_water_mm"
                                    class="form-control" placeholder="Enter Sample Add Water MM 1">
                                <div id="add_water_mm_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="mb-1">Add Water MM 2</label>
                                <input type="number" step="1" min="0" name="add_water_mm_2" id="add_water_mm_2"
                                    class="form-control" placeholder="Enter Sample Add Water MM 2">
                                <div id="add_water_mm_2_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="mb-1">Temp sand MM 1</label>
                                <input type="number" step="any" name="temp_sand_mm_1" id="temp_sand_mm_1"
                                    class="form-control" placeholder="Enter Sample Temp Sand MM 1">
                                <div id="temp_sand_mm_1_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="mb-1">RCS Pick Up</label>
                                <input type="number" step="any" name="rcs_pick_up" id="rcs_pick_up" class="form-control"
                                    placeholder="Enter Sample RCS Pick Up">
                                <div id="rcs_pick_up_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="mb-1">Total Flask</label>
                                <input type="number" step="any" name="total_flask" id="total_flask" class="form-control"
                                    placeholder="Enter Sample Total Flask">
                                <div id="total_flask_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="mb-1">RCS Avg</label>
                                <input type="number" step="any" name="rcs_avg" id="rcs_avg" class="form-control"
                                    placeholder="Enter Sample RCS Avg">
                                <div id="rcs_avg_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="mb-1">Add Bentonite MA</label>
                                <input type="number" name="add_bentonite_ma" id="add_bentonite_ma" class="form-control"
                                    placeholder="Enter Sample Add Bentonite MA">
                                <div id="add_bentonite_ma_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="mb-1">Total Sand</label>
                                <input type="number" step="any" name="total_sand" id="total_sand" class="form-control"
                                    placeholder="Enter Sample Total Sand">
                                <div id="total_sand_error" class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary mr-2 d-flex align-items-center"
                    data-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cancel
                </button>
                <button type="submit" class="btn btn-success mr-2 d-flex align-items-center" id="gsSubmitBtn">
                    <i class="ri-checkbox-circle-line me-1"></i> Submit
                </button>
            </div>
        </form>
    </div>
</div>