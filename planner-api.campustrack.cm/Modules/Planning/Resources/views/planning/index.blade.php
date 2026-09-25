@extends('templates.template_principal')
@section('main')
<!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('/adminlte/plugins/fontawesome-free/css/all.min.css')}}">
  <!-- DataTables -->
  <link rel="stylesheet" href="{{ asset('/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('/adminlte/dist/css/adminlte.min.css')}}">

  @yield('css')
</head>
<body class="hold-transition sidebar-mini">
  @include('partials.navbar')
    @include('partials.sidebar')
    <div class="content-wrapper">
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12 d-flex align-items-center">
                        <h2 class="content-header-title float-left mb-0 mr-2">
                            {{ __('planning::plannings.module_name') }}
                        </h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a
                                        href="{{ route('planning.plannings.index') }}">{{ __('planning::plannings.module_name') }}</a>
                                </li>
                                <li class="breadcrumb-item active">
                                    <a
                                        href="{{ route('planning.plannings.index') }}">{{ __('planning::plannings.list_title') }}</a>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-body">
            <div class="row">
                <div class="col-12 d-flex justify-content-end">
                    <button data-planning="" data-planning-route="{{ route('planning.plannings.store') }}" class="btn btn-secondary m-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="feather feather-plus" style="width: 20px; height: 20px">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="row" id="table-without-card">
                <div class="table-responsive" id="results">
                    <table class="table">
                        <thead>
                            <tr class="text-center">
                                <th>{{ __('N°') }}</th>
                                <th>{{ __('planning::plannings.starting_date') }}</th>
                                <th>{{ __('planning::plannings.ending_date') }}</th>
                                <th>{{ __('planning::plannings.type') }}</th>
                                <th>{{ __('planning::components.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($plannings as $planning)
                                <tr class="text-center">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="font-weight-bolder text-primary editable"
                                            data-original="{{ $planning->starting_date }}"
                                            data-id="{{ $planning->id }}" data-table="plannings"
                                            data-col="starting_date">{{ $planning->starting_date->format('d M Y') }}</span>
                                    </td>
                                    <td>
                                        <span class="font-weight-bolder text-primary editable"
                                            data-original="{{ $planning->ending_date }}" data-id="{{ $planning->id }}"
                                            data-table="plannings"
                                            data-col="ending_date">{{ $planning->ending_date->format('d M Y') }}</span>
                                    </td>
                                    <td>
                                        <span class="font-weight-bolder text-primary">{{ $planning->type }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            @if (session("user")->nom_table_index == "administrateurs")
                                                <button type="button"
                                                    class="btn btn-sm dropdown-toggle hide-arrow waves-effect waves-float waves-light"
                                                    data-toggle="dropdown">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-more-vertical">
                                                        <circle cx="12" cy="12" r="1"></circle>
                                                        <circle cx="12" cy="5" r="1"></circle>
                                                        <circle cx="12" cy="19" r="1"></circle>
                                                    </svg>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <form action="{{ route('planning.plannings.destroy', $planning) }}"
                                                        method="POST" class="d-inline px-0 dropdown-item"
                                                        onSubmit="return confirm('{{ __('planning::plannings.messages.delete_confirm') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-danger btn shadow-none">
                                                            <svg
                                                                class="mr-1 text-danger" xmlns="http://www.w3.org/2000/svg"
                                                                width="14" height="14" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                class="feather feather-trash text-danger">
                                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                                <path
                                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                                </path>
                                                            </svg>
                                                            {{ __('planning::components.common.delete') }}
                                                            </button>
                                                    </form>
                                                    <button type="button" class="dropdown-item w-100"
                                                        data-planning="{{ $planning }}" data-planning-route="{{ route('planning.plannings.update', $planning) }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14"
                                                            height="14" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="feather feather-edit text-primary">
                                                            <path
                                                                d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7">
                                                            </path>
                                                            <path
                                                                d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z">
                                                            </path>
                                                        </svg>
                                                        <span
                                                            class="ml-1">{{ __('planning::components.common.edit') }}</span>
                                                    </button>
                                                @endif
                                                <button type="button" class="dropdown-item w-100"
                                                    data-planning-view="{{ $planning }}">
                                                    <i class="mr-1 text-primary" data-feather="eye"></i>
                                                    {{ __('planning::components.common.view') }}
                                                </button>
                                                @if (session("user")->nom_table_index == "administrateurs")
                                                    @php
                                                        $planning_copy = $planning;
                                                        // for ($i = 0; $i < count($planning->shiftPlannings); $i++) { 
                                                            // $planning_copy->shiftPlannings[$i] = $planning->shiftPlannings[$i]->id;
                                                        // }
                                                        // echo '<script>console.log(' . json_encode($planning_copy->shiftPlannings) . ');</script>';
                                                        // dd($planning_copy);
                                                    @endphp
                                                    <button type="button" class="dropdown-item w-100 text-primary"
                                                        data-planning="{{ $planning_copy }}" data-planning-route="{{ route('planning.plannings.store', $planning_copy) }}" data-duplicate="true">
                                                        <i class="fa fa-copy mr-2" width="14"
                                                            height="14"></i>
                                                        <span
                                                            class="ml-1">{{ __('Dupliquer') }}</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-muted text-center">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        {{ __('planning::components.table.no_records') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between mt-1">
                        <div class="dataTables_paginate paging_simple_numbers">
                            {{ $plannings->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
     @include('../partials/footer')

<!-- jQuery -->
<script src="{{ asset('/adminlte/plugins/jquery/jquery.min.js')}}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- DataTables -->
<script src="{{ asset('/adminlte/plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{ asset('/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('/adminlte/dist/js/adminlte.min.js')}}"></script>
<!-- AdminLTE for demo purposes -->
<script src="{{ asset('/adminlte/dist/js/demo.js')}}"></script>
<!-- page script -->
<script>
  $(function () {
    $("#example1").DataTable();
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
    });


    $('[data-planning-view]').on('click', function() {
        let planning = $(this).data('planning-view');
        $('#ModalBody').find('form').empty();
        $('#ModalBody').find('form').nextAll().remove();
        $('#planningModal .modal-body').append(`
            <p class="text-capitalize">${planning.type}</p>
            <p><span class="font-weight-bolder">` + '{{ __('planning::plannings.starting_date') }}' + ` :</span> ${new Date(planning.starting_date).toLocaleDateString('en-US', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })}</p>
            <p><span class="font-weight-bolder">` + '{{ __('planning::plannings.ending_date') }}' + ` :</span> ${new Date(planning.ending_date).toLocaleDateString('en-US', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })}</p>
            <p><span class="font-weight-bolder">` + '{{ __('planning::plannings.description') }}' + ` :</span> ${planning.description ?? '<span class="font-weight-bolder">-</span>'}</p>
            <div class="row align-items-center mb-1">
                <div class="col-12 d-flex justify-content-between align-items-center mb-2">
                    <div class="row col-12 d-flex justify-content-start pr-2 pr-md-0 mr-1">
                        <div style="max-width: 400px" class="col-12 col-md-6 d-flex align-items-center mb-1 mb-md-0">
                            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center col-12">
                                <label style="min-width: 90px" class="col-4 align-self-start mr-1" for="section">{{ __('Section') }} :</label>
                                <select id="id_section" class="form-control col-12 col-md-8" id="section" name="section">
                                    <option value="null">
                                        Toutes les sections
                                    </option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}">
                                            {{ $section->nom_section }}
                                        </option>                                                
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="max-width: 400px" class="col-12 col-md-6 d-flex align-items-center mt-1 mt-md-0">
                            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center col-12">
                                <label class="col-5 align-self-start" for="classe_mere">{{ __('Classes mères') }} :</label>
                                <select id="id_classe_mere" class="form-control col-12 col-md-8" id="classe_mere" name="classe_mere">
                                    <option value="null">
                                        Toutes les classes mères
                                    </option>
                                    @foreach ($classes_meres as $classe_mere)
                                        <option value="{{ $classe_mere->id }}">
                                            {{ $classe_mere->nom_classe_mere }}
                                        </option>                                                
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <table id="time-table" class="table table-bordered table-responsive mx-auto" style="max-width: max-content">
                <input type="hidden" id="planning_id" value="{{ $planning->id }}" />
                <thead>
                    <tr class="text-center">
                        <th>{{ __('planning::shift_plannings.date') }}</th>
                        <th>{{ __('planning::shift_plannings.period') }}</th>
                        @foreach ($classes as $classe)
                            <th style="min-width: 90px">{{ $classe->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                {{-- <tbody>
                    @foreach ($groupedElementsArray as $key => $groupedElements)
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
                    @endforeach
                </tbody> --}}
            </table>
        `);
        $('#planningModalLabel').text('{{ __('planning::plannings.view_title') }}');
        
        $.ajax({
            url: '/plannings/plannings',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: 'planning_id=' + planning.id + '&bool=true',
            success: function (response) {
                // return console.log(response);
                ;
                $('#time-table').html(response.vue);
                $('#id_classe_mere').empty();
                $('#id_classe_mere').append('<option value="null">Toutes les classes mères</option>');
                Array.from(response.classes_meres).forEach((classe_mere) => {
                    $('#id_classe_mere').append('<option value="' + classe_mere.id + '">' + classe_mere.nom_classe_mere + '</option>');
                });
                
            },
            error: function (xhr, status, error) {
                console.log(error);
                if (xhr.responseJSON) {
                    console.log(xhr.responseJSON.message);
                }
            }
        });

        $('#planningModal').modal('show');
    });

    $('[data-planning]').on('click', function () {
        let planning = $(this).data('planning');
        let form = $('#ModalBody').find('form');
        if (form.length > 0) {
            let planningRoute = $(this).data('planning-route');
            if (planningRoute !== undefined) {
                form.data('action', planningRoute);
            } else {
                console.error('planning-route data attribute is missing');
            }

            let planning = $(this).data('planning');
            if (planning !== undefined) {
                let method = planning.id ? 'PUT' : 'POST';
                method = $(this).data('duplicate') ? 'POST' : method;
                form.data('method', method);
            } else {
                console.error('planning data attribute is missing');
            }
        } else {
            console.error('Form element is missing');
            return;
        }
        if ($(this).data('planning-route') === '{{ route('planning.plannings.store') }}') {
            $('#planningModalLabel').text('{{ __('planning::plannings.create_title') }}');
        } else {
            $('#planningModalLabel').text('{{ __('planning::plannings.edit_title') }}');
        }
        $.ajax({
            url: '/plannings/form/plannings/?id=' + $(this).data('planning').id,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'GET',
            success: function (response) {
                form.nextAll().remove();
                form.html(response);
                $('#planningModal').modal('show');
            },
            error: function (xhr, status, error) {
                console.log(xhr.responseJSON);
            }
        });

        $('#Form').submit(function (e) {
            e.preventDefault();
            let shift_plannings_ids = planning.shift_plannings?.map(function (shift_planning) {
                return shift_planning.id;
            }) || [];
            $.ajax({
                url: $(this).data('action'),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: $(this).data('method'),
                data: $(this).serialize() + '&shift_plannings=' + shift_plannings_ids.join(','),
                success: function (response) {
                    console.log(response);
                    location.reload();
                    $('#planningModal').modal('hide');
                },
                error: function (xhr, status, error) {
                    if (xhr.responseJSON) {
                        console.log(xhr.responseJSON.errors);
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function (key, value) {
                            let element = $('#' + key);
                            element.addClass('border border-danger');
                            element.parent().nextAll().remove();
                            element.parent().after('<p class="text-danger">' + value[0] + '</p>');
                        });
                    }
                }
            });
        });
    });

    $(document).on('change', '#id_classe_mere', function (e) {
        
        $.ajax({
            url: '/plannings/plannings',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: 'id_classe_mere=' + $('#id_classe_mere').val() + '&id_section=' + $('#id_section').val() + '&planning_id=' + $("#planning_id").val(),
            success: function (response) {
                $('#time-table').html(response);
            },
            error: function (xhr, status, error) {
                if (xhr.responseJSON) {
                    console.log(xhr.responseJSON.message);
                }
            }
        });
    });

    $(document).on('change', '#id_section', function (e) {
        $.ajax({
            url: '/plannings/plannings',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: 'id_section=' + $('#id_section').val() + '&bool=true' + '&planning_id=' + $("#planning_id").val(),
            success: function (response) {
                // if (typeof response === 'object' && response !== null) {
                //     Object.keys(response).forEach((key) => {
                //         console.log(key, response[key]);
                //     });
                // } else {
                //     console.error('Response is not an object:', response);
                // }
                $('#time-table').html(response.vue);
                $('#id_classe_mere').empty();
                $('#id_classe_mere').append('<option value="null">Toutes les classes mères</option>');
                Array.from(response.classes_meres).forEach((classe_mere) => {
                    $('#id_classe_mere').append('<option value="' + classe_mere.id + '">' + classe_mere.nom_classe_mere + '</option>');
                });
                
            },
            error: function (xhr, status, error) {
                console.log(error);
                if (xhr.responseJSON) {
                    console.log(xhr.responseJSON.message);
                }
            }
        });
    });
});
</script>

<!-- Page script -->

@endsection

<!-- Modal -->
<div class="modal fade" id="planningModal" tabindex="-1" aria-labelledby="planningModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="planningModalLabel">{{ __('planning::components.common.view') }}</h5>
                <button type="button" class="btn-close btn py-25 px-50" data-dismiss="modal"
                    aria-label="Close"><i style="font-size: 20px;" class="fas fa-times"></i></button>
            </div>
            <div id=ModalBody class="modal-body">
                <form id="Form">
                </form>
            </div>
        </div>
    </div>
</div>
