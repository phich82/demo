@extends('layouts.admin')

@section('content')
    <div class="container">
        <div class="card grid-box">
            <div class="panel-body">
                <div class="form-group">
                    <span style="font-size: 40px; margin-right: 30px;">マイル{{ $mileType === Constant::MILE_ACCUMULATION ? '積算' : '償還' }}</span>
                    <span><a href="{{ route('admin.mile.'.($mileType === Constant::MILE_ACCUMULATION ? 'redemption.' : '').'index') }}" style="text-decoration: underline;">マイル{{ $mileType === Constant::MILE_ACCUMULATION ? '償還' : '積算' }}</a></span>
                </div>
            </div>
            <div class="panel-body">
                <span style="font-size: 30px; margin-left: 15px;">マイル{{ $mileType === Constant::MILE_ACCUMULATION ? '積算' : '償還' }} - 基本設定</span>
                <div class="row col-sm-12" style="margin-top: 20px;">
                    <div class="col-sm-2 text-right" style="width: 100px; margin-bottom: 10px;">現在設定:</div>
                    <div class="col-sm-2">
                        @if (!empty($currentSetting))
                            @if ($mileType === Constant::MILE_ACCUMULATION)
                            <span>{{ $currentSetting['amount'] }}円=1マイル</span>
                            @else
                            <span>1マイル={{ $currentSetting['amount'] }}円</span>
                            @endif
                        @elseif (empty($scheduleSetting))
                            @can('admin')
                            <span>
                                <a href="{{ route('admin.mile.'.($mileType === Constant::MILE_ACCUMULATION ? '' : 'redemption.').'basic') }}" style="text-decoration: underline;">編集</a>
                            </span>
                            @endcan
                        @endif
                    </div>
                    <div class="col-sm-8" colspan="3" style="color: #9B9B9B;">
                        @if (!empty($currentSetting))
                            ({{ $currentSetting['plan_start_date'] }}以降)
                        @endif
                    </div>
                </div>
                <div class="row col-sm-12">
                    <div class="col-sm-2 text-right" style="width: 100px;">変更予定:</div>
                    @if (!empty($scheduleSetting))
                    <div class="col-sm-2">
                        <span>{{ $scheduleSetting->plan_start_date }}より</span>
                    </div>
                    <div class="col-sm-2">
                        @if ($mileType === Constant::MILE_ACCUMULATION)
                        <span>{{ $scheduleSetting->amount }}円=1マイル</span>
                        @else
                        <span>1マイル={{ $scheduleSetting->amount }}円</span>
                        @endif
                    </div>
                    <div class="col-sm-1">
                        <span>に変更</span>
                    </div>
                    <div class="col-sm-5">
                        @can('admin')
                        <span>
                            <a href="{{ route('admin.mile.'.($mileType === Constant::MILE_ACCUMULATION ? '' : 'redemption.').'basic') }}" style="text-decoration: underline;">編集</a>
                        </span>
                        @endcan
                    </div>
                    @else
                    <div class="col-sm-2">
                        @can('admin')
                            <span>
                                <a href="{{ route('admin.mile.'.($mileType === Constant::MILE_ACCUMULATION ? '' : 'redemption.').'basic') }}" style="text-decoration: underline;">編集</a>
                            </span>
                        @endcan
                    </div>
                    @endif
                </div>
            </div>
            <div class="panel-body">
                <div class="header">
                    <label style="font-size: 30px; margin-left: 15px;">マイル{{$mileType === Constant::MILE_ACCUMULATION ? '積算' : '償還' }} - プロモーション</label>
                    <a href="{{ route('admin.mile.'.($mileType === Constant::MILE_ACCUMULATION ? '' : 'redemption.').'csv') }}" id="csv" download="" class="btn csv-disabled" style="border: 1px solid #223F9A;"
                        <i class="fa fa-cloud-download"></i> CSVダウンロード
                    </a>
                </div>
                <div class="header">
                    <label>
                        <span class="form-inline" style="margin-left: 15px; font-size: 30px;">
                            <span id="presult"></span> 件
                        </span>
                    </label>
                    <label>
                        @can('admin')
                            <a href="{{ route('admin.mile.'.($mileType === Constant::MILE_ACCUMULATION ? '' : 'redemption.').'promotion.create') }}" class="btn btn-primary form-control">
                                <i class="fa fa-plus"></i> 新規プロモーションを作成
                            </a>
                        @endcan
                    </label>
                    <label>
                        <span class="form-inline">
                            <label class="form-group" style="margin-right: 10px;">並べ替え</label>
                            <select class="form-control form-group select-search" id="sort-activity-purchase" onchange="sort()">
                                <option value="1">期間の早い順</option>
                                <option value="2">期間の遅い順</option>
                                <option value="3">申込日の早い順</option>
                                <option value="4">申込日の遅い順</option>
                            </select>
                        </span>
                    </label>
                    <label>
                        <span class="form-inline">
                            <label class="form-group" style="margin-right:10px;">表示数</label>
                            <select class="form-control form-group select-search" id="rpp" onchange="sort()">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </span>
                    </label>
                </div>
                <div class="alert alert-danger err-message">
                    <ul class="errors"></ul>
                </div>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>
                                <label style="font-weight:bold;">単位</label>
                                <select class="form-control drop-down select-search" id="unit" onchange="sort()">
                                    <option value="">すべて</option>
                                    <option value="2">商品</option>
                                    <option value="1">エリア</option>
                                </select>
                            </th>
                            <th>
                                <a href="" class="tbl-title">商品・エリア</a>
                                <span class="input-group" style="width: 230px;">
                                    <input type="text" id="activityArea" class="input-search" onkeypress="filter(event)" onchange="filterWhenChange(event)" style="border-right: none; border-top-right-radius: 0; border-bottom-right-radius: 0; width: 180px;">
                                    <div class="input-group-append">
                                        <i class="material-icons icon-search input-group-text" style="background-color: #FFFFFF; color: #B0BEC5; border-left: none;">search</i>
                                    </div>
                                </span>
                            </th>
                            <th>
                                <a href="" class="tbl-title">期間</a>
                                <span class="input-group datepicker" placeholder="yyyy-mm-dd" style="width: 180px;">
                                    <input type="text" id="activityDate" class="input-search date" onkeypress="filter(event)" onchange="filterWhenChange(event)" onclick="showDatepicker(this, 1)" placeholder="yyyy-mm-dd" style="border-right: none; border-top-right-radius: 0; border-bottom-right-radius: 0; width: 120px;">
                                    <div class="input-group-append">
                                        <i class="material-icons icon-date input-group-text" onclick="showDatepicker(this, 1)" style="background-color: #FFFFFF; color: #B0BEC5; border-left: none;">date_range</i>
                                    </div>
                                </span>
                            </th>
                            <th>
                                <a href="" class="tbl-title">申し込み日</a>
                                <span class="input-group datepicker" placeholder="yyyy-mm-dd" style="width: 180px;">
                                    <input type="text" id="purchaseDate" class="input-search date" onkeypress="filter(event)" onchange="filterWhenChange(event)" onclick="showDatepicker(this, 1)" placeholder="yyyy-mm-dd" style="border-right: none; border-top-right-radius: 0; border-bottom-right-radius: 0; width: 120px;">
                                    <div class="input-group-append">
                                        <i class="material-icons icon-date input-group-text" onclick="showDatepicker(this, 1)" style="background-color: #FFFFFF; color: #B0BEC5; border-left: none;">date_range</i>
                                    </div>
                                </span>
                            </th>
                            <th>
                                <a href="" class="tbl-title">換算比率</a>
                            </th>
                            <th width="80" class="text-right">
                                <a href="" class="tbl-title">編集</a>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="data" id="data-promotion"></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/mile/mile.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/mile/mile.js') }}"></script>
@endpush