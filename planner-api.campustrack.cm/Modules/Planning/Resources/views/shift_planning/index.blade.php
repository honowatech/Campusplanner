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
                            {{ __('planning::shift_plannings.model_name') }}
                        </h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('planning.shift-plannings.index') }}">{{ __('planning::shift_plannings.model_name') }}</a>
                                </li>
                                <li class="breadcrumb-item active">
                                    <a href="{{ route('planning.shift-plannings.index') }}">{{ __('planning::shift_plannings.list_title') }}</a>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-body">
            <div class="row align-items-center mb-1">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="row col-8 d-flex justify-content-between pr-2 pr-md-0 mr-1">
                        <div class="col-12 col-md-8 d-flex align-items-center pb-50 pb-md-0">
                            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center col-12">
                                <label class="col-4 text-nowrap d-none d-md-block mr-3" for="planning">{{ __('planning::shift_plannings.planning') }} :</label>
                                <select class="form-control col-12 col-md-8" id="planning" name="planning">
                                    <option value="">
                                        All Plannings
                                    </option>
                                    @foreach($plannings as $planning)
                                        <option value="{{ $planning->id }}">
                                            {{ $planning->period }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- <div class="col-12 col-md-6 d-flex align-items-center pt-50 pt-md-0">
                            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center col-12">
                                <label class="col-5 text-nowrap d-none d-md-block" for="work_station">{{ __('planning::shift_plannings.work_station') }} :</label>
                                <select class="form-control col-12 col-md-8" id="work_station" name="work_station">
                                    <option value="">
                                        All Work Station
                                    </option>
                                    @foreach($workStations as $id => $name)
                                        <option value="{{ $id }}">
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}
                    </div>
                    <button class="btn btn-secondary mb-2 mb-md-0 align-self-end align-md-self-center mr-4" data-shift-planning="" data-shift-planning-route="{{ route('planning.shift-plannings.store') }}"
                        class="btn btn-gradient-secondary m-1"
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
                            class="feather feather-plus"
                            style="width: 20px; height: 20px"
                        >
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="row" id="table-without-card">
                <div class="table-responsive" id="results">
                    <x-planning::modules.shift_planning.shift_plannings_list :shiftPlannings="$shiftPlannings" />
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
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Modal -->
    <div class="modal fade" id="shiftPlanningModal" tabindex="-1" aria-labelledby="shiftPlanningModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shiftPlanningModalLabel">{{ __('planning::components.common.update') }}</h5>
                    <button type="button" class="btn-close btn py-25 px-50" data-dismiss="modal" aria-label="Close"><i style="font-size: 20px;" class="fas fa-times"></i></button>
                </div>
                <div id="ModalBody" class="modal-body">
                    <form id="Form">

                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('[data-shift-planning-view]').on('click', function () {
                let shiftPlanning = $(this).data('shift-planning-view');
                $('#shiftPlanningModal .modal-title').text(new Date(shiftPlanning.date).toLocaleDateString('en-US', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }));
                $('#ModalBody').find('form').empty();
                $('#ModalBody').find('form').nextAll().remove();
                $('#shiftPlanningModal .modal-body').append(`
                    <p>${new Date(shiftPlanning.date).toLocaleDateString('en-US', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })}</p>
                    <p><span class="fw-bold">` + '{{ __('planning::shift_plannings.starting_hour') }}' + ` :</span> ${new Date(shiftPlanning.starting_hour).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</p>
                    <p><span class="fw-bold">` + '{{ __('planning::shift_plannings.ending_hour') }}' + ` :</span> ${new Date(shiftPlanning.ending_hour).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</p>
                `);
                $('#shiftPlanningModalLabel').text('{{ __('planning::shift_plannings.view_title') }}');
                $('#shiftPlanningModal').modal('show');
            });

            $('[data-shift-planning]').on('click', function () {
                let shiftPlanning = $(this).data('shift-planning');
                let form = $('#ModalBody').find('form');
                if (form.length > 0) {
                    let shiftPlanningRoute = $(this).data('shift-planning-route');
                    if (shiftPlanningRoute !== undefined) {
                        form.data('action', shiftPlanningRoute);
                    } else {
                        console.error('shift-planning-route data attribute is missing');
                    }

                    let shiftPlanning = $(this).data('shift-planning');
                    if (shiftPlanning !== undefined) {
                        let method = shiftPlanning.id ? 'PUT' : 'POST';
                        form.data('method', method);
                    } else {
                        console.error('shift-planning data attribute is missing');
                    }
                } else {
                    console.error('Form element is missing');
                    return;
                }
                if ($(this).data('planning-route') === '{{ route('planning.shift-plannings.store') }}') {
                    $('#shiftPlanningModalLabel').text('{{ __('planning::shift_plannings.create_title') }}');
                } else {
                    $('#shiftPlanningModalLabel').text('{{ __('planning::shift_plannings.edit_title') }}');
                }
                // $('#shiftPlanningModal .modal-title').text(new Date(shiftPlanning.date).toLocaleDateString('en-US', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }));
                $.ajax({
                    url: '/plannings/form/shift-plannings/?id=' + $(this).data('shift-planning').id,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: 'GET',
                    success: function (response) {
                        form.nextAll().remove();
                        form.html(response);
                        $('#shiftPlanningModal').modal('show');
                        $.getScript( "{{ asset('app-assets/vendors/js/forms/select/select2.full.min.js') }}", function( data, textStatus, jqxhr ) {
                            console.log( data ); // Data returned
                            console.log( textStatus ); // Success
                            console.log( jqxhr.status ); // 200
                            console.log( "Load was performed." );
                        });
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseJSON);
                    }
                });
            });

            $('#Form').submit(function (e) {
                e.preventDefault();
                $("p.text-danger").prev().find('input').removeClass('border-danger');
                $("p.text-danger").remove();
                $.ajax({
                    url: $(this).data('action'),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: $(this).data('method'),
                    data: $(this).serialize(),
                    success: function (response) {
                            console.log(response);
                            // sessionStorage.setItem('success', '{{ __('planning::shift_plannings.messages.updated') }}');
                            location.reload();
                        $('#shiftPlanningModal').modal('hide');
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseJSON.errors);
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function (key, value) {
                            if (xhr.responseJSON) {
                                console.log(xhr.responseJSON);
                                let errors = xhr.responseJSON.errors;
                                $.each(errors, function (key, value) {
                                    let element = $('#' + key);
                                    element.addClass('border border-danger');
                                    element.parent().nextAll().remove();
                                    element.parent().after('<p class="text-danger">' + value[0] + '</p>');
                                });
                            }
                        });
                    }
                });
            });

            $('#planning').val('');
            $('#work_station').val('');
            $('#planning').on('change', () => {
                let workStation = undefined;
                if ($('#work_station').val() !== '') {
                    workStation = $('#work_station').val();
                }
                $.ajax({
                    url: '/plannings/filter/shift-plannings?planning_id=' + $('#planning').val() + '&work_station_id=' + workStation,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: 'GET',
                    success: function (response) {
                        $('#results').html(response);
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseJSON);
                    }
                })
            })

            $('#work_station').on('change', () => {
                let planning = undefined;
                if ($('#planning').val() !== '') {
                    planning = $('#planning').val();
                }
                $.ajax({
                    url: '/plannings/filter/shift-plannings?planning_id=' + planning + '&work_station_id=' + $('#work_station').val(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: 'GET',
                    success: function (response) {
                        $('#results').html(response);
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseJSON);
                    }
                })
            })
        });
    </script>
