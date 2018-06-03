@if (!empty($data))
    @foreach ($data as $row)
    <tr class="rowActive">
        <th scope="row">
            <a class="idActivity" href="javascript:void(0)" style="text-decoration: underline;">{{ $row->id }}</a>
        </th>
        <td>
            <a class="titleActivity" href="javascript:void(0)" style="text-decoration: underline;">{{ $row->title }}</a>
        </td>
    </tr>
    @endforeach
@endif