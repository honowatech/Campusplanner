<table class="table">
    <thead>
        <tr class="text-center">
            <th>{{ __('N°') }}</th>
            <th>{{ __('planning::shift_plannings.date') }}</th>
            <th>{{ __('planning::shift_plannings.period') }}</th>
            {{-- <th>{{ __('planning::shift_plannings.enseignants') }}</th> --}}
            <th>{{ __('planning::shift_plannings.status') }}</th>
            <th>{{ __('planning::components.common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($shiftPlannings as $shiftPlanning)
            <tr class="text-center">
            <td>{{ $loop->iteration }}</td>
            <td>
                <span
                    class="font-weight-bolder text-primary text-nowrap"
                    data-original="{{ $shiftPlanning->date->format('D d M Y') }}"
                    data-id="{{ $shiftPlanning->id }}"
                    data-table="shift_plannings"
                    data-col="date"
                    >{{ $shiftPlanning->date->format('D d M Y') }}</span
                >
            </td>
            <td>
                <span
                    class="font-weight-bolder text-primary text-nowrap"
                    data-original="{{ $shiftPlanning->period }}"
                    data-id="{{ $shiftPlanning->id }}"
                    data-table="shift_plannings"
                    data-col="starting_hour"
                    >{{ $shiftPlanning->period }}</span
                >
            </td>
        {{-- <td>
            @foreach ($shiftPlanning->enseignants as $enseignant)
                <span
                    class="font-weight-bolder badge badge-light-info mb-25 mt-25"
                    ><i class="fas fa-user mr-1"></i>{{ $enseignant->identifiant[$loop->index]->nom }}</span
                >
                @if ($loop->last)
                    <br />
                @endif
            @endforeach
        </td> --}}
            <td>
                @switch ($shiftPlanning->status)
                    @case('canceled')
                        <span class="badge badge-danger p-50 rounded-lg"> </span>
                        @break
                    @case('completed')
                        <span class="badge badge-success p-50 rounded-lg"> </span>
                        @break
                    @case('ongoing')
                        <span class="badge badge-primary p-50 rounded-lg"> </span>
                        @break
                    @case('pending')
                        <span class="badge badge-warning p-50 rounded-lg"> </span>
                        @break
                    @default
                @endswitch
            </td>
            <td>
                <div>
                    <button
                        type="button"
                        class="btn btn-sm dropdown-toggle hide-arrow waves-effect waves-float waves-light"
                        data-toggle="dropdown"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="feather feather-more-vertical"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="1"
                            ></circle>
                            <circle
                                cx="12"
                                cy="5"
                                r="1"
                            ></circle>
                            <circle
                                cx="12"
                                cy="19"
                                r="1"
                            ></circle>
                        </svg>
                    </button>
                    <div class="dropdown-menu">
                        <form
                            action="{{ route('planning.shift-plannings.destroy', $shiftPlanning) }}"
                            method="POST"
                            class="d-inline px-0 dropdown-item"
                            onSubmit="return confirm('{{ __('planning::shift_plannings.messages.delete_confirm') }}')"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-danger btn shadow-none">
                                <svg
                                    class="mr-1 text-danger"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="14"
                                    height="14"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="feather feather-trash text-danger"
                                >
                                    <polyline
                                        points="3 6 5 6 21 6"
                                    ></polyline>
                                    <path
                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                                    ></path>
                                </svg>
                                {{ __('planning::components.common.delete') }}
                            </button>
                        </form>
                        <button type="button"
                            class="dropdown-item w-100" data-shift-planning="{{ $shiftPlanning }}"
                            data-shift-planning-route="{{ route('planning.shift-plannings.update', $shiftPlanning) }}"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="feather feather-edit text-primary"
                            >
                                <path
                                    d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
                                ></path>
                                <path
                                    d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"
                                ></path>
                            </svg>
                            <span class="ml-1"
                                >{{ __('planning::components.common.edit') }}</span
                            >
                        </button>
                        <button type="button" class="dropdown-item w-100" data-shift-planning-view="{{ $shiftPlanning }}">
                            <i class="mr-1 text-primary" data-feather="eye"></i>
                            {{ __('planning::components.common.view') }}
                        </button>
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-muted text-center">
                <i class="fas fa-exclamation-triangle"></i> {{ __('planning::components.table.no_records') }}
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
{{-- <div class="d-flex justify-content-between mt-1">
    <div class="dataTables_paginate paging_simple_numbers">
        {{ $shiftPlannings->links() }}
    </div>
</div> --}}
