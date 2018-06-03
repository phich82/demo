@if (!empty($data))
    @foreach ($data as $row)
        <tr class="rowActive">
            <td>
            <a href="javascript:void(0)" data-id="{{ $row->area_path }}" data-title="{{ $row->area_path_jp }}" style="text-decoration: underline;">{{ $row->area_path_jp }}</a>
            </td>
        </tr>
    @endforeach
@endif