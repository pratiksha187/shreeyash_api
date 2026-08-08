@php
    $projectForLinks = $project ?? null;
    $workflowSteps = [
        [
            'number' => '1',
            'title' => 'Project Creation',
            'detail' => 'New project received, create project master.',
            'href' => route('admin.projects.create'),
            'button' => 'Create Project',
        ],
        [
            'number' => '2',
            'title' => 'Project Structure',
            'detail' => 'Phase -> Layer -> Task -> Sub-task.',
            'href' => $projectForLinks ? route('admin.projects.structure', $projectForLinks) : route('admin.projects.index'),
            'button' => 'Open Structure',
        ],
        [
            'number' => '3',
            'title' => 'BOQ / SOR / Budget',
            'detail' => 'Add BOQ sheet, SOR rates, quantities, and budget.',
            'href' => $projectForLinks ? route('admin.projects.boq', $projectForLinks) : route('admin.projects.index'),
            'button' => 'Open BOQ',
        ],
        [
            'number' => '4',
            'title' => 'Project Planning',
            'detail' => 'Plan time, material, labour, and machinery.',
            'href' => $projectForLinks ? route('admin.projects.show', $projectForLinks).'#project-planning' : route('admin.projects.index'),
            'button' => 'Plan Work',
        ],
    ];
@endphp

<section class="card pm-flow">
    <div class="pm-flow-head">
        <div>
            <span>New Project Received</span>
            <strong>Simple Project Flow</strong>
        </div>
    </div>
    <div class="pm-flow-steps">
        @foreach ($workflowSteps as $step)
            <a class="pm-flow-step" href="{{ $step['href'] }}">
                <span class="pm-flow-number">{{ $step['number'] }}</span>
                <span class="pm-flow-text">
                    <strong>{{ $step['title'] }}</strong>
                    <small>{{ $step['detail'] }}</small>
                    <em>{{ $step['button'] }}</em>
                </span>
            </a>
        @endforeach
    </div>
</section>
