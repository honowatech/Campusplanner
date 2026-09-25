@csrf
<div class="row">
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="name">{{ __('planning::work_stations.name') }}</label>
            <input working="text" id="name"
                class="form-control @error('name') border border-danger @enderror" name="name"
                value="{{ old('name') ?? $workStation->name }}"
                placeholder="{{ __('planning::work_stations.placeholders.name')}}" />
        </div>
        @error('name')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="department">{{ __('planning::work_stations.department') }}</label>
            <input working="text" id="department"
                class="form-control @error('department') border border-danger @enderror" name="department"
                value="{{ old('department') ?? $workStation->department }}"
                placeholder="{{ __('planning::work_stations.placeholders.department')}}" />
        </div>
        @error('department')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="max_teachers">{{ __('planning::work_stations.max_teachers') }}</label>
            <input type="number" id="max_teachers"
                class="form-control @error('max_teachers') border border-danger @enderror" name="max_teachers"
                value="{{ old('max_teachers') ?? $workStation->max_teachers }}"
                placeholder="{{ __('planning::work_stations.placeholders.max_teachers')}}" />
        </div>
        @error('max_teachers')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="working">{{ __('planning::work_stations.working') }}</label>
            <select working="number" id="working"
                class="form-control @error('working') border border-danger @enderror" name="working">
                <option value="1" {{ old('working', $workStation->working) === '1' ? 'selected' : '' }}>
                    {{ __('Working') }}
                </option>
                <option value="0" {{ old('working', $workStation->working) === '0' ? 'selected' : '' }}>
                    {{ __('Inactive') }}
                </option>
            </select>
        </div>
        @error('working')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="description"></label>
            <textarea id="description" name="description" class="form-control" placeholder="{{ __('planning::work_stations.placeholders.description') }}">{{ old('description') ?? $workStation->description }}</textarea>
        </div>
        @error('working')
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
