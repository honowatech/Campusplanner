@csrf
<div class="row">
    <div class="col-12">
        <div class="form-group">
            <label for="type">{{ __('planning::plannings.type') }}</label>
            <select id="type"
                class="form-control @error('type') border border-danger @enderror" name="type"
                value="{{ old('type') ?? $planning->type }}">
                <option value="weekly" {{ (old('type') ?? $planning->type) == 'weekly' ? 'selected' : '' }}>{{ __('planning::plannings.type_values.weekly') }}</option>
                <option value="monthly" {{ (old('type') ?? $planning->type) == 'monthly' ? 'selected' : '' }}>{{ __('planning::plannings.type_values.monthly') }}</option>
            </select>
        </div>
        @error('type')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="starting_date">{{ __('planning::plannings.starting_date') }}</label>
            <input type="date" id="starting_date"
                class="form-control @error('starting_date') border border-danger @enderror" name="starting_date"
                value="{{ old('starting_date') ?? $planning->starting_date?->format('Y-m-d') ?? '' }}"
                placeholder="{{ __('planning::plannings.placeholders.starting_date')}}" />
        </div>
        @error('starting_date')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label for="ending_date">{{ __('planning::plannings.ending_date') }}</label>
            <input type="date" id="ending_date"
                class="form-control @error('ending_date') border border-danger @enderror" name="ending_date"
                value="{{ old('ending_date') ?? $planning->ending_date?->format('Y-m-d') ?? '' }}"
                placeholder="{{ __('planning::plannings.placeholders.ending_date')}}" />
        </div>
        @error('ending_date')
            <p class="text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="description">{{ __('planning::plannings.description') }}</label>
            <textarea id="description"
                class="form-control @error('description') border border-danger @enderror" name="description"
                placeholder="{{ __('planning::plannings.placeholders.description')}}">{{ old('description') ?? $planning->description }}</textarea>
        </div>
        @error('description')
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
