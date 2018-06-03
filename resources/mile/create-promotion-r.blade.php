@extends('layouts.admin')

@section('content')
    <div class="container">
        <div class="card grid-box">
            <div class="panel-body">
                <div class="row">
                    <span class="col-md-8" style="font-size: 40px;">マイル償還 - プロモーション</span>
                    <span class="col-md-4 text-right" style="line-height: 40px; height: 40px;">
                        <a href="{{ route('admin.mile.redemption.index') }}"><< マイル償還トップへ戻る</a>
                    </span>
                </div>
            </div>
            <div class="panel-body">
                <div class="container">
                    {{-- error messages --}}
                    <div class="alert alert-danger err-message">
                        <ul class="errors"></ul>
                    </div>
                    <div class="row">
                        <div class="col-md-2 text-right" style="height: 42px; line-height: 42px;">
                            <label>単位</label>
                        </div>
                        <div class="col-md-10" style="padding-left:0; margin-bottom: 20px;">
                            <div class="btn-group" role="group" aria-label="Unit" style="border: 1px solid grey; border-radius: 5px;">
                                <button type="button" onclick="toggleUnit({{ Constant::UNIT_ACTIVITY }})" class="btn {{ old('unit', Constant::UNIT_ACTIVITY) == Constant::UNIT_ACTIVITY ? ' btn-primary' : '' }} btnActivity">商品</button>
                                <button type="button" onclick="toggleUnit({{ Constant::UNIT_AREA }})" class="btn {{ old('unit', Constant::UNIT_ACTIVITY) == Constant::UNIT_AREA ? ' btn-primary' : '' }} btnArea">エリア</button>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                            {{-- form new promotion --}}
                            <form method="post" action="{{ route('admin.mile.redemption.promotion.store') }}" data-back="{{ route('admin.mile.redemption.index') }}" id="frm">
                                @csrf
                                <input type="hidden" name="mile_type" value="{{ Constant::MILE_REDEMPTION }}" />
                                <input type="hidden" name="unit" value="{{ Constant::UNIT_ACTIVITY }}" />

                                <div class="row rowActivity">
                                    <div class="col-md-2 text-right">
                                        <label>商品</label>
                                    </div>
                                    <div class="col-md-10" style="padding-left:0;">
                                        <input type="text" name="activity_title" value="" class="label" style="border: none; width: 100%; display: none;" readonly />
                                        <input type="hidden" name="activity_id" value="" />
                                        <p>
                                            <a href="javascript:void(0)" data-toggle="modal" data-target="#activityModal" id="selActivity">
                                                商品を選ぶ
                                            </a>
                                        </p>
                                    </div>
                                </div>
                                <div class="row rowArea" style="display: none;">
                                    <div class="col-md-2 text-right">
                                        <label>エリア</label>
                                    </div>
                                    <div class="col-md-10" style="padding-left: 0;">
                                        <label id="area-path-name"></label>
                                        <input type="hidden" name="area_path"/>
                                        <p>
                                            <a href="javascript:void(0)" data-toggle="modal" data-target="#areaPathModal" id="selAreaPath">
                                                エリアを選ぶ
                                            </a>
                                        </p>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-md-2 text-right" style="height: 42px; line-height: 42px;">
                                        <label>期間</label>
                                    </div>
                                    <div class="col-md-10">
                                        <div class="row">
                                            <div class="col-xs-2" style="height: 42px; line-height: 42px;">
                                                <label>開始</label>
                                            </div>
                                            <div class="col-xs-4" style="margin-left: 20px; margin-right: 30px;">
                                                <span class="input-group datepicker" placeholder="yyyy-mm-dd" style="width: 180px;">
                                                    <input type="text" name="activity_start_date" value="" onclick="showDatepicker(this, 1)" class="form-control date" placeholder="yyyy-mm-dd" style="border-right: none;">
                                                    <div class="input-group-append">
                                                        <i class="material-icons icon-date input-group-text" onclick="showDatepicker(this, 1)" style="background-color: #FFFFFF; color: #B0BEC5;">date_range</i>
                                                    </div>
                                                </span>
                                            </div>
                                            <div class="col-xs-2" style="height: 42px; line-height: 42px;">    
                                                <label>終了</label>
                                            </div>
                                            <div class="col-xs-4" style="margin-left: 20px;">
                                                <span class="input-group datepicker" placeholder="yyyy-mm-dd" style="width: 180px;">
                                                    <input type="text" name="activity_end_date" value="" onclick="showDatepicker(this, 1)" class="form-control date" placeholder="yyyy-mm-dd" style="border-right: none;">
                                                    <div class="input-group-append">
                                                        <i class="material-icons icon-date input-group-text" onclick="showDatepicker(this, 1)" style="background-color: #FFFFFF; color: #B0BEC5;">date_range</i>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-md-2 text-right" style="height: 42px; line-height: 42px;">
                                        <label>申し込み日</label>
                                    </div>
                                    <div class="col-md-10">
                                        <div class="row">
                                            <div class="col-xs-3" style="height: 42px; line-height: 42px;">
                                                <span>開始</span>
                                            </div>
                                            <div class="col-xs-3" style="margin-left: 20px; margin-right: 30px;">
                                                <span class="input-group datepicker" placeholder="yyyy-mm-dd" style="width: 180px;">
                                                    <input type="text" name="purchase_start_date" value="" onclick="showDatepicker(this, 1)" class="form-control date" placeholder="yyyy-mm-dd" style="border-right: none;">
                                                    <div class="input-group-append">
                                                        <i class="material-icons icon-date input-group-text" onclick="showDatepicker(this, 1)" style="background-color: #FFFFFF; color: #B0BEC5;">date_range</i>
                                                    </div>
                                                </span>
                                            </div>
                                            <div class="col-xs-3" style="height: 42px; line-height: 42px;">    
                                                <label>終了</label>
                                            </div>
                                            <div class="col-xs-3" style="margin-left: 20px;">
                                                <span class="input-group datepicker" placeholder="yyyy-mm-dd" style="width: 180px;">
                                                    <input type="text" name="purchase_end_date" value="" onclick="showDatepicker(this, 1)" class="form-control date" placeholder="yyyy-mm-dd" style="border-right: none;">
                                                    <div class="input-group-append">
                                                        <i class="material-icons icon-date input-group-text" onclick="showDatepicker(this, 1)" style="background-color: #FFFFFF; color: #B0BEC5;">date_range</i>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-md-2 text-right" style="height: 42px; line-height: 42px;">
                                        <label>積算比率</label>
                                    </div>
                                    <div class="col-md-10" style="height: 100%; line-height: 100%;">
                                        <div class="row">
                                            <div class="col-xs-12" style="height: 42px; line-height: 42px;">
                                                <input type="radio" name="rate_type" onclick="tickRateType(1)" value="{{ Constant::ACCUMULATION_RATE_TYPE_VARIABLE }}" checked="checked"> <span>変動額</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12" style="height: 42px; line-height: 42px;">
                                                <input type="radio" name="rate_type" onclick="tickRateType(2)" value="{{ Constant::ACCUMULATION_RATE_TYPE_FIXED }}"> <span>固定額</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <!-- when tick on radio 'Change': show '1 Mile= [...]Yen', hide '[...] PLUS' -->
                                            <!-- when tick on radio 'Fix': hide '1 Mile= [...]Yen', show '[...] PLUS' -->
                                            <div class="col-xs-12" style="height: 42px; line-height: 42px;">
                                                <span class="plusMile" style="display: none;">一律 </span>
                                                <span class="asMile" style="display: inline;">1マイル=</span>
                                                <input type="text" name="amount" value="" class="form-control" style="width: 70px; display: inline;"> 
                                                <span class="asMile" style="display: inline;">円</span>
                                                <span class="plusMile" style="display: none;">マイル割引</span>
                                            </div>
                                        </div>
                                        <!-- when tick on radio 'Change': show bellow, hidden when tick on radio 'Fix' -->
                                        <div class="row">
                                            <div class="currentSetting" style="margin-top: 20px;">
                                                @if (!empty($currentSetting))
                                                    <label>現在の基礎設定：1マイル={{ $currentSetting->amount }}円</label><br>
                                                    <label>※換算後に端数が出た場合は四捨五入されます</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <button type="button" onClick="savePromotion()" class="btn btn-primary">保存する</button>
                                        </div>
                                    </div>
                                </div> 
                            </form>                       
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{--  popup for activity search  --}}
        <div class="modal fade" id="activityModal" tabindex="-1" role="dialog" aria-labelledby="activityModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="border-bottom: none;">
                        <h4 class="modal-title" id="activityModalLabel">商品を検索し、選択してください。</h4>
                    </div>
                    <div class="modal-body">
                        <div class="input-group">
                            <label for="aid" class="col-form-label col-md-3" style="margin-right: 10px;">商品IDで検索</label>
                            <input type="text" class="form-control" 
                                   data-url="{{ route('admin.mile.search_activities') }}"
                                   onkeyup="searchByActivityID(event)" 
                                   name="activityID" id="aid"
                            >
                        </div>
                        <div class="input-group" style="margin-top: 10px;">
                            <label for="a-title" class="col-form-label col-md-3" style="margin-right: 10px;">商品名で検索</label>
                            <input type="text" class="form-control" 
                                   data-url="{{ route('admin.mile.search_activities_title') }}"
                                   onkeyup="searchByActivityTitle(event)" 
                                   name="activityTitle" id="a-title"
                            >
                        </div>
                        <div style="margin-top: 20px;">
                            <div class="msgSearchActivity" style="margin-bottom: 5px; color: red;"></div>
                            <div class="text-center loading" style="color:green;font-style:italic;display:none;">Processing...</div>
                            <div style="margin-bottom: 5px;">
                                <span class="resultSearchActivity">0</span> 件
                            </div>
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr style="background-color: grey;">
                                        <th scope="col">ID</th>
                                        <th scope="col">商品名</th>
                                    </tr>
                                </thead>
                                <tbody class="listActivity"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="text-center" style="margin-bottom: 30px;">
                        <button type="button" class="btn btn-primary btnClose" data-dismiss="modal">閉じる</button>
                    </div>
                </div>
            </div>
        </div>

        {{--  popup for area search --}}
        <div class="modal fade" id="areaPathModal" tabindex="-1" role="dialog" aria-labelledby="areaPathModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="border-bottom: none;">
                        <h4 class="modal-title" id="areaPathModalLabel">エリアを検索し、選択してください。</h4>
                    </div>
                    <div class="modal-body">
                        <div class="input-group">
                            <label for="aid" class="col-form-label col-md-3" style="margin-right: 10px; padding-left: 0;">エリアで検索</label>
                            <input type="text" class="form-control" name="areaPath" 
                                   data-url="{{ route('admin.mile.area.filter_pathjp') }}"
                                   onkeyup="searchByAreaPath(event)" 
                                   id="aid"
                            >
                        </div>
                        <div style="margin-top: 20px;">
                            <div class="msgSearchArea" style="margin-bottom: 5px; color: red;"></div>
                            <div class="text-center loading" style="color:green;font-style:italic;display:none;">Processing...</div>
                            <div style="margin-bottom: 5px;">
                                <span class="resultSearchAreaPath">0</span> 件
                            </div>
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr style="background-color: grey;">
                                        <th scope="col">エリア</th>
                                    </tr>
                                </thead>
                                <tbody class="listAreaPath"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="text-center" style="margin-bottom: 30px;">
                        <button type="button" class="btn btn-primary btnClose" data-dismiss="modal">閉じる</button>
                    </div>
                </div>
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