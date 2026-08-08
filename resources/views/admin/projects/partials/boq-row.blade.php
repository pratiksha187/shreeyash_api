@php
    $values = $aggregate ?: [
        'scope_qty' => (float) $item->scope_qty,
        'rate' => (float) $item->rate,
        'amount' => (float) $item->scope_qty * (float) $item->rate,
    ];
@endphp

<td>
    <span class="boq-tree-cell">
        @if ($item->item_type === 'group')
            <button class="boq-toggle" type="button" data-boq-toggle="{{ $groupKey }}" aria-expanded="true" title="Expand / collapse child rows">-</button>
        @else
            <span class="boq-toggle-placeholder"></span>
        @endif
        <span class="boq-no-text">{{ $item->boq_no ?: '-' }}</span>
    </span>
</td>
<td>
    <div class="boq-title-cell">
        @if ($isChild)
            <span class="boq-indent">|--</span>
        @endif
        <div>
            <strong>{{ $item->task_name }}</strong>
            @if ($item->item_type === 'group')
                <div class="table-subtext"><span class="boq-group-badge">Heading</span></div>
            @endif
        </div>
    </div>
</td>
<td>{{ $item->item_type === 'group' ? '-' : ($item->unit ?: '-') }}</td>
<td>{{ $item->item_type === 'group' && ! $aggregate ? '-' : number_format($values['scope_qty'], 3) }}</td>
<td>{{ $item->item_type === 'group' ? '-' : number_format($values['rate'], 2) }}</td>
<td><strong>{{ $item->item_type === 'group' && ! $aggregate ? '-' : number_format($values['amount'], 2) }}</strong></td>
<td>
    <div class="boq-row-actions">
        @if ($item->item_type === 'group')
            <button
                class="boq-icon-btn"
                type="button"
                data-add-child
                data-parent-boq="{{ $item->boq_no }}"
                data-parent-group-name="{{ $item->group_name ?: $item->task_name }}"
                title="Add item under this heading"
            >+</button>
        @endif
        <form class="boq-delete-form" method="POST" action="{{ route('admin.projects.boq.destroy', [$project, $item]) }}" onsubmit="return confirm('Delete this BOQ row?')">
            @csrf
            @method('DELETE')
            <button class="boq-icon-btn danger" type="submit" title="Delete row">x</button>
        </form>
    </div>
</td>
