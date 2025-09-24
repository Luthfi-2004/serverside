<!-- modal -->
<div class="modal fade" id="modal-ace" tabindex="-1" role="dialog" aria-labelledby="aceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <form id="aceForm" class="modal-content" autocomplete="off">
            @csrf

            <!-- hidden -->
            <input type="hidden" name="id" id="ace_id">
            <input type="hidden" name="form_mode" id="ace_mode" value="create">
            <input type="hidden" name="date" id="mDate">
            <input type="hidden" name="shift" id="mShift"><!-- shift auto -->

            <!-- header -->
            <div class="modal-header">
                <h5 class="modal-title" id="aceModalLabel"><span id="aceModalMode">Add</span> ACE LINE</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>

            <!-- body -->
            <div class="modal-body">
                <div id="aceFormAlert" class="alert alert-danger d-none mb-3"></div>

                <!-- card -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label mb-1">Type Product</label>
                                <input type="text" name="product_type_name" id="mProductName" class="form-control" placeholder="Input Type Product">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label mb-1">Sample Start</label>
                                <input type="time" name="sample_start" id="mStart" class="form-control" placeholder="Input Sample Start">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label mb-1">Sample Finish</label>
                                <input type="time" name="sample_finish" id="mFinish" class="form-control" placeholder="Input Sample Finish">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab-mm" role="tab">
                            <i class="ri-flask-line mr-1"></i> MM Sample
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-add" role="tab">
                            <i class="ri-pie-chart-2-line mr-1"></i> Additive Additional
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-bc13" role="tab">
                            <i class="ri-alert-line mr-1"></i> Pengecekan BC13
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- mm -->
                    <div class="tab-pane fade show active" id="tab-mm" role="tabpanel">
                        <div class="row">
                            @php
                                $mmFields = [
                                    ['p', 'P'],
                                    ['c', 'C'],
                                    ['gt', 'G.T'],
                                    ['cb_lab', 'Cb Lab'],
                                    ['moisture', 'Moisture'],
                                    ['machine_no', 'Nomor Mesin'],
                                    ['bakunetsu', 'Bakunetsu'],
                                    ['ac', 'AC'],
                                    ['tc', 'TC'],
                                    ['vsd', 'VSD'],
                                    ['ig', 'IG'],
                                    ['cb_weight', 'CB Weight'],
                                    ['tp50_weight', 'TP 50 Weight'],
                                    ['ssi', 'SSI'],
                                ];
                            @endphp
                            @foreach($mmFields as [$name, $label])
                                @php $labelText = str_replace('_', ' ', $label); @endphp
                                <div class="col-md-3 mb-3">
                                    <label class="mb-1">{{ $labelText }}</label>
                                    <input
                                        type="{{ $name === 'machine_no' ? 'text' : 'number' }}"
                                        step="{{ $name === 'machine_no' ? null : '0.01' }}"
                                        name="{{ $name }}" id="m_{{ $name }}" class="form-control"
                                        placeholder="Input Sample {{ $labelText }}">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- add -->
                    <div class="tab-pane fade" id="tab-add" role="tabpanel">
                        <div class="row">
                            @php
                                $addFields = [
                                    ['dw29_vas', 'DW29_VAS'],
                                    ['dw29_debu', 'DW29_Debu'],
                                    ['dw31_vas', 'DW31_VAS'],
                                    ['dw31_id', 'DW31_ID'],
                                    ['dw31_moldex', 'DW31_Moldex'],
                                    ['dw31_sc', 'DW31_SC'],
                                ];
                            @endphp
                            @foreach($addFields as [$name, $label])
                                @php $labelText = str_replace('_', ' ', $label); @endphp
                                <div class="col-md-3 mb-3">
                                    <label class="mb-1">{{ $labelText }}</label>
                                    <input type="number" step="0.01" name="{{ $name }}" id="m_{{ $name }}" class="form-control"
                                           placeholder="Input Sample {{ $labelText }}">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- bc13 -->
                    <div class="tab-pane fade" id="tab-bc13" role="tabpanel">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="mb-1">NO Mix</label>
                                <input type="number" step="1" min="0" name="no_mix" id="mNoMix" class="form-control"
                                       placeholder="Input Sample NO Mix">
                            </div>
                            @php
                                $bc13Fields = [
                                    ['bc13_cb', 'BC13_CB'],
                                    ['bc13_c', 'BC13_C'],
                                    ['bc13_m', 'BC13_M'],
                                ];
                            @endphp
                            @foreach($bc13Fields as [$name, $label])
                                @php $labelText = str_replace('_', ' ', $label); @endphp
                                <div class="col-md-3 mb-3">
                                    <label class="mb-1">{{ $labelText }}</label>
                                    <input type="number" step="0.01" name="{{ $name }}" id="m_{{ $name }}" class="form-control"
                                           placeholder="Input Sample {{ $labelText }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- footer -->
            <div class="modal-footer d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary mr-2 d-flex align-items-center" data-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cancel
                </button>
                <button type="submit" class="btn btn-success mr-2 d-flex align-items-center" id="aceSubmitBtn">
                    <i class="ri-checkbox-circle-line me-1"></i> Submit
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var $ = window.jQuery;

    function todayYmd() {
        var t = new Date();
        var y = t.getFullYear();
        var m = String(t.getMonth() + 1).padStart(2, '0');
        var d = String(t.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function detectShiftByNow() {
        var hh = (new Date()).getHours();
        if (hh >= 6 && hh < 16) return 'D';
        if (hh >= 16 && hh < 22) return 'S';
        return 'N';
    }

    $('#modal-ace').on('show.bs.modal', function () {
        var isUpdate = $('#ace_mode').val() === 'update';
        if (isUpdate) return;
        $('#aceForm')[0].reset();
        $('#ace_mode').val('create');
        $('#aceFormAlert').addClass('d-none').empty();
        $('#mDate').val(todayYmd());
        $('#mShift').val(detectShiftByNow());
    });
})();
</script>
@endpush
