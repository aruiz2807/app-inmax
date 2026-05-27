@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'label' => null,
    'triggerLabel' => null,
    'placeholder' => null,
    'searchable' => false,
    'multiple' => false,
    'clearable' => false,
    'disabled' => false,
    'icon' => null,
    'iconAfter' => 'chevron-up-down',
    'checkIcon' => 'check',
    'checkIconClass' => null,
    'invalid' => null,
    'triggerClass' => null,
    'searchEmit' => null,
    'loadMoreEmit' => null,
])
@php
    // Extract wire:model property and check if .live modifier exists
    $modelAttrs = collect($attributes->getAttributes())
        ->keys()
        ->first(fn($key) => str_starts_with($key, 'wire:model'));
    
    $model = $modelAttrs ? $attributes->get($modelAttrs) : null;

    $isLive = $modelAttrs && str_contains($modelAttrs, '.live');
@endphp

<div 
    x-data="
        function(){
        
        const $entangle = (prop, live) => {

            const binding = $wire.$entangle(prop);

            return live ? binding.live : binding;
        };

        const $initState = (prop, live, multiple) => {
            // when the env is not livewire
            if (!prop) return multiple ? [] : null;

            return  $entangle(prop, live);
        };


        return{
            search: '',
            open: false,
            isTyping: false,
            
            // Manages visual highlighting of options (not real browser focus)
            // Real focus stays on input for accessibility, this just controls which option appears highlighted
            activeIndex: null,
            
            // Store all available options and currently visible/filtered options
            options:[],        // All options from DOM
            filteredOptions:[], // Subset based on search query
            
            isMultiple: @js($multiple),
            isDisabled: @js($disabled),
            isSearchable: @js($searchable),
            searchEmit: @js($searchEmit),
            loadMoreEmit: @js($loadMoreEmit),
            
            // Selected value(s) - array for multiple, single value for single select
            state: $initState(@js($model), @js($isLive), @js($multiple)),
                    
            placeholder: @js($placeholder) ?? 'select ...',

            syncOptions() {
                this.options = Array
                    .from(this.$el.querySelectorAll('[data-slot=option]:not([hidden])'))
                    .map((option) => ({
                        value: option.dataset.value,
                        label: option.dataset.label,
                        element: option
                    }));

                if (this.search.trim() === '' || this.searchEmit) {
                    // If searching remotely or empty search, show all currently rendered options
                    this.filteredOptions = this.options;
                } else {
                    // Filter by search query locally
                    this.filteredOptions = this.options.filter(option => this.contains(option.label,this.search));
                }
            },

            init() {
                this.$nextTick(() => {
                    this.syncOptions();

                    // Only initialize from Alpine x-model when there is no Livewire wire:model binding.
                    if (!@js($model) && this.$root?._x_model?.get) {
                        this.state = this.$root._x_model.get();
                    }
                });

                this.$watch('state', (value) => {
                    // Sync with Alpine.js x-model
                    this.$root?._x_model?.set(value);
                    // Emit change event
                    this.$dispatch('change', { value });
                });

                // Filter options based on search input
                this.$watch('search', (val) => {
                    if (this.searchEmit) {
                        this.$dispatch(this.searchEmit, { search: val });
                    }

                    if (val.trim() === '') {
                        // Empty search → show all options 
                        this.filteredOptions = this.options;
                    } else if (!this.searchEmit) {
                        // Filter locally only if not emitting for remote search
                        this.filteredOptions = this.options.filter(option => this.contains(option.label,val));
                    }
                })
            },

            // Check if given option is currently selected
            isSelected(option) {
                return this.isMultiple ? this.state?.some(item => item == option) : this.state == option;
            },

            select(option) {
                this.isTyping = false;
                this.search = '';

                if (!this.isMultiple) {
                    // Single select: set value and close
                    this.open = false;
                    this.state = option;
                    return;
                }

                // Multiple select: toggle option in/out of array
                if(!Array.isArray(this.state)){
                    console.error('Multiple select requires an array value. Please bind an array property using x-model or wire:model.');
                }        
                
                const itemIndex = this.state.findIndex(item => item == option);
                
                if (itemIndex === -1) {
                    this.state.push(option);    // Add to selection
                } else {
                    this.state.splice(itemIndex, 1);  // Remove from selection
                }
            },

            // Reset component to initial state
            clear() {
                this.state = this.isMultiple ? [] : '';
                this.open = false;
            },

            // Determine if option should be visible (for search filtering)
            isItemShown(value) {
                if (!this.isSearchable || !this.isTyping) return true;
                if (this.searchEmit) return true;
                return this.contains(value, this.search);
            },

            // Close dropdown and reset all temporary states
            close() {
                this.open = false;
                this.search = '';
                this.isTyping = false;
                this.activeIndex = null;
            },

            // Toggle dropdown open/closed state
            toggle() {
                if (this.isDisabled) return;
                
                this.open = !this.open;
                
                // Auto-highlight first option when opening searchable select with no selection
                if((this.open && !this.hasSelection) && this.isSearchable){
                    this.activeIndex = 0
                };
            },

            // Keyboard navigation handler - manages visual highlighting (not real focus)
            // Real browser focus stays on input for screen readers, this just moves the visual highlight
            handleKeydown(event) {
                // Navigate down through options (wraps to beginning)
                if (event.key === 'ArrowDown') {
                    if (this.activeIndex === null || this.activeIndex >= this.filteredOptions.length - 1) {
                        this.activeIndex = 0;
                    } else {
                        this.activeIndex++;
                    }
                }

                // Navigate up through options (wraps to end)
                if (event.key === 'ArrowUp') {
                    if (this.activeIndex === null || this.activeIndex <= 0) {
                        this.activeIndex = this.filteredOptions.length - 1;
                    } else {
                        this.activeIndex--;
                    }
                }

                // Select currently highlighted option
                if (event.key === 'Enter' && this.activeIndex !== null) {
                    let option = this.filteredOptions[this.activeIndex];
                    this.select(option.value);
                }
                
                // Jump to first option
                if (event.key === 'Home') {
                    this.activeIndex = 0;
                    return;
                }

                // Jump to last option
                if (event.key === 'End') {
                    this.activeIndex = this.filteredOptions.length - 1;
                    return;
                }
            },
            
            // Convert option value to its index in the filtered results array
            getFilteredIndex(value) {
                return this.filteredOptions.findIndex(option => option.value == value);
            },
            
            // Mouse hover handler - sync visual highlight with mouse position is like converting hover state to our *virtual* focus
            handleMouseEnter(value) {
                this.activeIndex = this.getFilteredIndex(value);
            },
            
            handleMouseLeave(el){
                // Only blur if searchable (input has focus)
                if(this.isSearchable){
                    el.blur();
                }
                // Uncomment to clear highlight when mouse leaves (preference: keep activeIndex for better keyboard nav)
                // this.activeIndex = null;
            },
            
            // Check if option should appear visually highlighted
            isFocused(value) {
                const index = this.getFilteredIndex(value);
                return this.activeIndex !== null && index !== -1 && index === this.activeIndex;
            },
            
            // Check if search returned any results
            get hasFilteredResults() {
                return this.filteredOptions.length > 0;
            },
            
            // Generate display text for the trigger button
            get label() {
                if (!this.hasSelection) return this.placeholder;

                const findOption = (val) => {
                    let opt = this.options.find(o => o.value == val);
                    if (!opt) {
                        this.syncOptions(); // Just-in-time sync if not found
                        opt = this.options.find(o => o.value == val);
                    }
                    return opt;
                };

                if (!this.isMultiple) {
                    // Single select: show the selected option's label
                    const option = findOption(this.state);
                    return option?.label ?? this.state;
                }

                // Multiple select: show individual label or count
                if (this.state.length === 1) {
                    const option = findOption(this.state[0]);
                    return option?.label ?? this.state[0];
                }

                return ` ${this.state.length} items selected`;
            },
            
            // Check if any option is currently selected
            get hasSelection() {
                return this.isMultiple ? this.state?.length > 0 : (this.state !== null && this.state !== '');
            },
            
            contains(str, substring){
                return str.toLowerCase().trim().includes(substring.toLowerCase().trim());
            } 
        }
    }"
    x-effect="syncOptions()"
    {{ $attributes->class([
            'relative [--popup-round:var(--radius-box)] [--popup-padding:--spacing(1)]',
            'dark:border-red-400! dark:shadow-red-400 text-red-400! placeholder:text-red-400!' => $invalid,
        ]),
     }}
>

    @if ($name)
        <input 
            type="hidden" 
            name="{{ $name }}" 
            x-bind:value="isMultiple ? state.join(',') : state"
        />
    @endif

    <div>
        <x-ui.select.trigger/>

        <x-ui.select.options 
            :checkIconClass="$checkIconClass"
            :checkIcon="$checkIcon"
        >
            {{ $slot }}
        </x-ui.select.options>
    </div>
</div>
