@csrf
<div class="row">
    <div class="col-12">
        <div class="form-group">
            <label for="planning_id">{{ __("planning::shift_plannings.planning") }}</label>
            <select id="planning_id" class="select2 form-control @error('planning_id')
                invalid
            @enderror" name="planning_id">
                @foreach ($plannings as $planning)
                    <option value="{{ $planning->id }}" {{ (old('planning_id') == $planning->id || $shiftPlanning->planning_id == $planning->id) ? 'selected' : '' }}>{{ $planning->period }}</option>
                @endforeach
            </select>
        </div>
        @error('planning_id')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>


    <div class="col-12">
        <div class="form-group">
            <label for="course_class_id">{{ __("planning::shift_plannings.classe") }}</label>
            <select id="course_class_id" class="select2 form-control @error('course_class_id')
                invalid
            @enderror" name="course_class_id">
                @foreach ($classes as $id => $nom)
                    <option value="{{ $id }}" {{ (old('course_class_id') == $id || $shiftPlanning->course_class_id == $id) ? 'selected' : '' }}>{{ $nom }}</option>
                @endforeach
            </select>
        </div>
        @error('course_class_id')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="course_id">{{ __("planning::shift_plannings.matiere") }}</label>
            <select id="course_id" class="select2 form-control @error('course_id')
                invalid
            @enderror" name="course_id">
                @foreach ($courses as $id => $nom)
                    <option value="{{ $id }}" {{ (old('course_id') == $id || $shiftPlanning->course_id == $id) ? 'selected' : '' }}>{{ $nom }}</option>
                @endforeach
            </select>
        </div>
        @error('course_id')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-12">
        <label for="teacher_id">{{ __('planning::shift_plannings.enseignants')}}</label>
        <div class="form-group">
            <select class="select2 form-control" id="teacher_id" name="teacher_id" data-placeholder="{{ __('planning::shift_plannings.placeholders.enseignant') }}" style="width: 100%;">
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ (old('teacher_id') == $teacher->id || $shiftPlanning->teacher_id == $teacher->id) ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
                @endforeach
            </select>
        </div>
        @error('teacher_id')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="date">{{ __('planning::shift_plannings.date') }}</label>
            <input type="date" id="date"
                class="form-control @error('date') border border-danger @enderror" name="date"
                value="{{ old('date', $shiftPlanning->date?->format('Y-m-d') ?? '') }}"
                placeholder="{{ __('planning::shift_plannings.placeholders.date')}}" />
        </div>
        @error('date')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="starting_hour">{{ __('planning::shift_plannings.starting_hour') }}</label>
            <input type="time" id="starting_hour"
                class="form-control @error('starting_hour') border border-danger @enderror" name="starting_hour"
                value="{{ old('starting_hour') ?? $shiftPlanning->starting_hour?->format('H:i') }}"
                placeholder="{{ __('planning::shift_plannings.placeholders.starting_hour')}}" />
        </div>
        @error('starting_hour')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="ending_hour">{{ __('planning::shift_plannings.ending_hour') }}</label>
            <input type="time" id="ending_hour"
                class="form-control @error('ending_hour') border border-danger @enderror" name="ending_hour"
                value="{{ old('ending_hour') ?? $shiftPlanning->ending_hour?->format('H:i') }}"
                placeholder="{{ __('planning::shift_plannings.placeholders.ending_hour')}}" />
        </div>
        @error('ending_hour')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="notes">{{ __('planning::shift_plannings.notes') }}</label>
            <textarea id="notes"
                class="form-control @error('notes') border border-danger @enderror" name="notes" placeholder="{{ __('planning::shift_plannings.placeholders.notes') }}">{{ old('notes') ?? $shiftPlanning->notes }}</textarea>
        </div>
        @error('note')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="d-flex gap-2 justify-content-end col-12">
        <button type="reset" data-dismiss="modal" class="btn btn-outline-danger mr-1 waves-effect">
            {{ __('planning::components.common.cancel') }}
        </button>
        <button type="submit" class="btn btn-success waves-effect waves-float waves-light">
            {{ __('planning::components.common.save') }}
        </button>
    </div>
</div>
