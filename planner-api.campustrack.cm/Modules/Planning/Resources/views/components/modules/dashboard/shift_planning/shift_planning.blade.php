<div class="row">
    <div class="col-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    {{ __('planning::shift_plannings.model_name') }}
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                        <div class="media">
                            <div class="avatar bg-light-warning mr-2">
                                <div class="avatar-content">
                                    <i style="font-size: 16px;" class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="media-body my-auto">
                                <h4 class="font-weight-bolder mb-0">{{ $shiftPlanningWithoutEmployee ?? '0' }}</h4>
                                <p class="card-text font-small-3 mb-0">{{ __('Shift non attributed') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                        <div class="media">
                            <div class="avatar bg-light-primary mr-2">
                                <div class="avatar-content">
                                    <i style="font-size: 16px;" class="fas fa-hourglass"></i>
                                </div>
                            </div>
                            <div class="media-body my-auto">
                                <h4 class="font-weight-bolder mb-0">{{ $shiftPlanningOngoing ?? '0' }}</h4>
                                <p class="card-text font-small-3 mb-0">{{ __('Shift ongoing') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                        <div class="media">
                            <div class="avatar bg-light-success mr-2">
                                <div class="avatar-content">
                                    <i style="font-size: 16px;" class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="media-body my-auto">
                                <h4 class="font-weight-bolder mb-0">{{ $shiftPlanningCompleted ?? '0' }}</h4>
                                <p class="card-text font-small-3 mb-0">{{ __('Shift completed') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                        <div class="media">
                            <div class="avatar bg-light-danger mr-2">
                                <div class="avatar-content">
                                    <i style="font-size: 16px;" class="fa-regular fa-classic fa-circle-xmark"></i>
                                </div>
                            </div>
                            <div class="media-body my-auto">
                                <h4 class="font-weight-bolder mb-0">{{ $shiftPlanningCanceled ?? '0' }}</h4>
                                <p class="card-text font-small-3 mb-0">{{ __('Shift canceled') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    {{ __('planning::shift_plannings.model_name') }}
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <canvas id="totalRecords" aria-label="chart" role="img">

                        </canvas>
                        <p class="font-weight-bolder text-center mt-1">{{ ($shiftPlanningTotal ?? '0') . ' ' . __('planning::shift_plannings.model_name') . ' ' . __('this week') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
