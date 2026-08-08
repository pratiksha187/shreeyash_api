<tr class="structure-row-{{ $item->structure_type }}">
    <td><span class="structure-type-pill">{{ $structureTypes[$item->structure_type] ?? 'Task' }}</span></td>
    <td>
        <div class="structure-name">
            <span class="structure-indent" style="--depth: {{ $depth }}"></span>
            <strong>{{ $depth > 0 ? '|-- ' : '' }}{{ $item->title }}</strong>
        </div>
    </td>
    <td>{{ $item->work_area ?: '-' }}</td>
    <td>{{ $item->sort_order }}</td>
    <td>
        <div class="structure-actions">
            <button
                class="boq-icon-btn"
                type="button"
                data-add-structure-child
                data-parent-id="{{ $item->id }}"
                data-parent-type="{{ $item->structure_type }}"
                data-parent-area="{{ $item->work_area }}"
                title="Add child row"
            >+</button>
            <form class="structure-delete-form" method="POST" action="{{ route('admin.projects.structure.destroy', [$project, $item]) }}" onsubmit="return confirm('Delete this row and its child rows?')">
                @csrf
                @method('DELETE')
                <button class="boq-icon-btn danger" type="submit">x</button>
            </form>
        </div>
    </td>
</tr>

@foreach ($item->children as $child)
    @include('admin.projects.partials.structure-row', ['item' => $child, 'project' => $project, 'structureTypes' => $structureTypes, 'depth' => $depth + 1])
@endforeach
