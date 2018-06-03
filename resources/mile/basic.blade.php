@extends('layouts.admin')

@section('content')
    <div class="container">
        <div class="row" style="margin-top: 20px;">
            <span class="col-md-6" style="font-size: 30px;">マイル{{ $mileType === Constant::MILE_ACCUMULATION ? '積算' : '償還' }} - 基本設定変更</span>
            <span class="col-md-6 text-right" style="line-height: 30px;">
                <a href="{{ route('admin.mile.'.($mileType === Constant::MILE_ACCUMULATION ? '' : 'redemption.').'index') }}"><< マイル{{ $mileType === Constant::MILE_ACCUMULATION ? '積算' : '償還' }}トップへ戻る</a>
            </span>
        </div>
        <div class="card grid-box">
            <div class="panel-body">
                <div class="container">
                    <div class="alert alert-danger err-message">
                        <ul class="errors"></ul>
                    </div>
                    <form method="post" action="{{ route('admin.mile.'.($mileType === Constant::MILE_ACCUMULATION ? '' : 'redemption.').'store') }}" id="frm" data-back="{{ route('admin.mile.'.($mileType === Constant::MILE_ACCUMULATION ? '' : 'redemption.').'basic') }}">
                        {{ csrf_field() }}
                        <div class="row info-detail">
                            <div class="row col-md-12 margin-bottom-10">
                                <div class="col-md-2">現在設定:</div>
                                <div class="col-md-10 no-padding-left">
                                    @if(!empty($currentSetting))
                                        @if ($mileType === Constant::MILE_ACCUMULATION)
                                        <span>{{ $currentSetting->amount }}円＝1マイル</span>
                                        @else
                                        <span>1マイル={{ $currentSetting->amount }}円</span>
                                        @endif
                                        <span>({{ $currentSetting->plan_start_date }} 以降)</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row col-md-12">
                                <div class="col-md-2">変更予定:</div>
                                <div class="col-md-10">
                                    <div class="scheduledList">
                                        @if (!empty($scheduledSetting))
                                            @foreach ($scheduledSetting as $k => $row)
                                            <div class="row row-setting" id="id-{{ $k }}" data-key="{{ $k }}">
                                                <label style="width: 50px; margin-right: 0; padding-right: 0;">{{ $k === 0 ? '開始日' : '' }}</label>
                                                <span class="input-group datepicker" placeholder="yyyy-mm-dd">
                                                    <input type="text" value="{{ $row->plan_start_date }}" data-key="{{ $k }}" onchange="updateMile(this)" onclick="showDatepicker(this)" class="form-control date" style="width: 70px;">
                                                    <i class="material-icons icon-date" onclick="showDatepicker(this)">date_range</i>
                                                </span>
                                                @if ($mileType === Constant::MILE_REDEMPTION)
                                                <span class="control-label">1マイル=</span>
                                                @endif
                                                <span>
                                                    <input type="number" value="{{ $row->amount }}" data-key="{{ $k }}" onchange="updateMile(this)" class="form-control amount" style="width: 70px;">
                                                </span>
                                                @if ($mileType === Constant::MILE_ACCUMULATION)
                                                <span class="control-label">円＝1マイル</span>
                                                @else
                                                <span class="control-label">円</span>
                                                @endif
                                                <span>
                                                    <a href="javascript:void(0)" data-key="{{ $k }}" onclick="deleteMile(this)" class="btn btn-link text-left" style="width: 80px; border:none; padding-left:0; margin-left: 10px;" title="Delete">削除</a>
                                                </span>
                                            </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div class="row col-md-12" style="padding-left: 0; margin-top: 30px; margin-bottom: 20px;">
                                        <a href="javascript:void(0)" class="col-md-3 text-left" onclick="addLine({{ isset($mileType) ? $mileType : 2 }})" title="Add Scheduled Basic Setting" style="padding-left: 0; text-decoration: underline;">変更予定を追加する</a>
                                    </div>
                                    <div class="row">
                                        <span class="border-0" colspan="4" style="padding-left:0;">※ 換算後に端数が出た場合は四捨五入されます。</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row col-md-12 text-center loading" style="color:green;font-style:italic;display:none;">Processing...</div>
                        <div class="row col-md-12 text-right">
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn btn-primary btnSave" onclick="reqAjaxUpdateMile()" title="Save" {!! count($scheduledSetting) === 0 ? ' disabled="disabled"' : '' !!}>保存する</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/mile/mile.css') }}">
@endpush

@push('scripts')
    <script>
        var data = '<?php echo json_encode($scheduledSetting ? $scheduledSetting : []); ?>';
        var newData = [];

        JSON.parse(data).map(function (val) {
            newData.push({
                id: val.id, //val.mile_basic_setting_id,
                date: val.plan_start_date,
                amount: val.amount,
                status: 0 // 0: old value, 1: update, 2 delete, 3 new
            });
        });

        $(function () {
            // enable Save button
            if (newData.length) {
                enableSaveBtn();
            }
        });
    </script>
    <script src="{{ asset('js/mile/mile.js') }}"></script>
@endpush