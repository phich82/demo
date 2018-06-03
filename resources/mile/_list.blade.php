@if (!empty($result['data']['promotions']))
    {{-- promotions list --}}
    @foreach ($result['data']['promotions'] as $item)
        <tr>
            <td class="border-0">
                <label class="text-center">{{ $item->unit == Constant::UNIT_ACTIVITY ? '商品' : 'エリア' }}</label>
            </td>
            <td class="border-0" style="width: 200px;">
                <label>{{ $item->unit == Constant::UNIT_ACTIVITY ? (!empty($result['data']['activities'][$item->activity_id]) ? $result['data']['activities'][$item->activity_id] : $item->activity_title) : $item->area_path_jp }}</label>
            </td>
            <td class="border-0">
                <label>{{ $item->activity_start_date }} ~ {{ $item->activity_end_date ? $item->activity_end_date : '期限なし' }}</label>
            </td>
            <td class="border-0">
                <label>{{ $item->purchase_start_date }} ~ {{ $item->purchase_end_date ? $item->purchase_end_date : '期限なし' }}</label>
            </td>
            @if ($mileType === Constant::MILE_ACCUMULATION)
            <td class="border-0">
                <label>{{ $item->rate_type !== Constant::ACCUMULATION_RATE_TYPE_FIXED ? $item->amount.'円＝1マイル' : $item->amount.'マイルPLUS' }}</label>
            </td>
            <td class="border-0 text-right">
                @can('admin')
                    <a href="{{ route('admin.mile.promotion.edit', ['id' => $item->promotion_id]) }}" class="btn-link" style="text-decoration: underline;">
                        <i class="fa fa-pencil"></i> 編集
                    </a>
                @endcan
            </td>
            @elseif ($mileType === Constant::MILE_REDEMPTION)
            <td class="border-0">
                <label>{{ $item->rate_type == Constant::ACCUMULATION_RATE_TYPE_VARIABLE ? '1マイル='.$item->amount.'円' : $item->amount.'マイルOFF' }}</label>
            </td>
            <td class="border-0 text-right">
                @can('admin')
                    <a href="{{ route('admin.mile.redemption.promotion.edit', ['id' => $item->promotion_id]) }}" class="btn-link" style="text-decoration: underline;">
                        <i class="fa fa-pencil"></i> 編集
                    </a>
                @endcan
            </td>
            @endif
        </tr>
    @endforeach
    @if ($result['data']['promotions']->total() === 0)
    <tr>
        <td colspan="6" class="text-center">
            <center>
                <h1>IMAGE HERE</h1>
                <h3>指定された条件では見つかりませんでした</h3><br>
                <h5>正しくキーワードや数値を入力してください。または、検索条件を変更してください。</h5>
            </center>
        </td>
    </tr>
    @endif
    <tr class="paginate">
        <td colspan="4">
            {{ $result['data']['promotions']->links() }}
        </td>
        <td colspan="2" class="border-0 text-right">
            <span id="totalPromotions" data-total="{{ $result['data']['promotions']->total() }}" style="color: #9B9B9B; font-family: "Noto Sans CJK JP"; font-size: 14px; font-weight: 500;">
            {{ $result['data']['promotions']->total() }} 件中 {{ $result['data']['promotions']->currentPage() }}-{{ $result['data']['promotions']->lastPage() }} を表示
            </span>
        </td>
    </tr>
@else
    <tr>
        <td colspan="6" class="text-center">
            <center>
                <h1>IMAGE HERE</h1>
                <h3>指定された条件では見つかりませんでした</h3><br>
                <h5>正しくキーワードや数値を入力してください。または、検索条件を変更してください。</h5>
            </center>
        </td>
    </tr>
    <tr class="paginate">
        <td colspan="4"></td>
        <td colspan="2" class="border-0 text-right">
            <span id="totalPromotions" data-total="0" style="color: #9B9B9B; font-family: "Noto Sans CJK JP"; font-size: 14px; font-weight: 500;">
                0 件中 1-{{ $result['data']['perPage'] }} を表示
            </span>
        </td>
    </tr>
@endif