@props(['name', 'title', 'options', 'required' => false])

<div class="flex flex-col lg:flex-row text-xs font-semibold lg:text-sm space-y-2 sm:space-y-0">
    <label class="w-full lg:w-1/3 label" for="{{$name}}">{{__($title)}}</label>
    <div class="flex flex-col space-y-2 w-full lg:w-2/3 mt-1 lg:mt-0">
        <select {{ $attributes->except('class') }} name="{{$name}}" id="{{$name}}" 
                class="text-sm text-dark bg-dark w-full rounded-md h-10
                @error( $attributes->wire('model')->value() ) border-error @enderror"
        >
            <option value="">{{__('common.select')}}...</option>
            @foreach ($options as $option_key => $option_value)
                <option value="{{$option_key}}">{{$option_value}}</option>
            @endforeach
        </select>
        @error( $attributes->wire('model')->value() )
            <div class="text-error">{{$message}}</div>
        @enderror
    </div>
</div>