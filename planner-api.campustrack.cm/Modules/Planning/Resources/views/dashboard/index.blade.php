<x-planning::layout>
    <div class="content-wrapper">
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title float-left mb-0">
                            {{ __('planning::dashboard.dashboard') }}
                        </h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('planning.dashboard') }}">{{ __('planning::dashboard.dashboard') }}</a>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-body">
            <x-planning::modules.dashboard.shift_planning.shift_planning :shiftPlanningWithoutEmployee="$shiftPlanningWithoutEmployee" :shiftPlanningOngoing="$shiftPlanningOngoing" :shiftPlanningCompleted="$shiftPlanningCompleted" :shiftPlanningCanceled="$shiftPlanningCanceled" :shiftPlanningTotal="$shiftPlanningTotal" />
        </div>
    </div>
    <x-slot:footer>
        <x-planning::footer/>
    </x-slot>
    <script src="{{ asset('app-assets/vendors/js/charts/chart.min.js') }}"></script>
    <script>
        var pending = @json(__('planning::shift_plannings.status_values.pending'));
        var ongoing = @json(__('planning::shift_plannings.status_values.ongoing'));
        var completed = @json(__('planning::shift_plannings.status_values.completed'));
        var canceled = @json(__('planning::shift_plannings.status_values.canceled'));
        var shiftPlanningWithoutEmployee = @json($shiftPlanningWithoutEmployee);
        var shiftPlanningOngoing = @json($shiftPlanningOngoing);
        var shiftPlanningCompleted = @json($shiftPlanningCompleted);
        var shiftPlanningCanceled = @json($shiftPlanningCanceled);
    </script>
    <script src="{{asset('modules/planning/js/calendar/jquery.min.js')}}"></script>
    {{-- <script src="{{asset('modules/planning/js/calendar/popper.js')}}"></script>
    <script src="{{asset('modules/planning/js/calendar/main.js')}}"></script> --}}
    <script src="{{ asset('modules/planning/js/dashboard.js') }}"></script>
</x-planning::layout>
