<input type="hidden" id="planning_id" value="{{ $planning->id }}" />
<thead>
    <tr class="text-center">
        <th>{{ __('planning::shift_plannings.date') }}</th>
        <th>{{ __('planning::shift_plannings.period') }}</th>
        @foreach ($classes as $classe)
            <th style="min-width: 90px">{{ $classe->nom }}</th>
        @endforeach
    </tr>
</thead>
<tbody>
    @forelse ($groupedElementsArray as $key => $groupedElements)
        <tr class="text-center">
            <td class="my-auto" rowspan="{{ count($groupedElements) + 1 }}">
                <span class="font-weight-bolder text-primary text-nowrap">
                    {{ \Carbon\Carbon::parse($key)->format('D d M Y') }}
                </span>
            </td>
        </tr>
        @foreach ($groupedElements as $period)
            <tr class="text-center">
                <td class="my-auto">
                    <span class="font-weight-bolder text-nowrap">
                        {{ $period[0]->period }}
                    </span>
                </td>
                @foreach ($classes as $classe)
                    <td>
                        @php
                            $shiftPlanning = collect($period)->firstWhere('course_class_id', $classe->id);
                        @endphp
                        @if ($shiftPlanning)
                            <span class="font-weight-bolder mb-25 mt-25">
                                {{ $shiftPlanning->course?->name }}
                            </span>
                            <br/><br/>
                            <span>{{ $shiftPlanning->teacher?->full_name }}</span>
                        @endif
                    </td>
                @endforeach
            </tr>    
        @endforeach
    @empty
        <tr>
            <td colspan="{{ count($classes) + 2 }}" class="text-muted text-center">
                <i class="fas fa-exclamation-triangle"></i>
                {{ __('planning::components.table.no_records') }}
            </td>
        </tr>
    @endforelse
</tbody>