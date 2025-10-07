<fieldset class="fieldset shift-content"
          x-data="{
              working: false,
              localAvailableTime: [],
              isDisabled: false,
              init() {
                  this.localAvailableTime = [...$wire.form.availableTime];

                  // Watch changes in localAvailableTime and update Livewire property
                  this.$watch('localAvailableTime', (value) => {
                      // Filter only available times with data and preserve daysOfWeek
                      const filtered = value.filter(item =>
                          (item.availableStartTime || item.availableEndTime || item.allDay) &&
                          item.daysOfWeek && item.daysOfWeek.length > 0
                      );
                      $wire.form.availableTime = filtered;
                  }, { deep: true });
              }
          }"
>
    <legend class="legend">{{ __('healthcare-services.available_time') }}</legend>

    <div class="form">
        <div class="form-group mb-4">
            <button @click.prevent="working = !working"
                    x-text="working ? '{{ __('forms.remove_work_schedule') }}' : '{{ __('forms.work_schedule') }}'"
                    class="item-add"
            >
                {{ __('add_work_schedule') }}
            </button>
        </div>

        <div x-cloak
             x-show="working"
             class="p-4 rounded-lg bg-blue-100 flex items-start mb-4"
        >
            @icon('alert-circle', 'w-5 h-5 text-blue-500 mr-3 mt-1')
            <div>
                <p class="font-bold text-blue-800">{{ __('forms.important') }}</p>
                <p class="text-sm text-blue-600">{{ __('healthcare-services.available_time_info') }}</p>
            </div>
        </div>

        <div x-cloak
             x-show="working"
             class="grid md:grid-cols-2 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700"
        >
            @foreach ($weekdays as $key => $day)
                <div
                    class="p-6 min-h-[220px] {{ $loop->iteration % 2 === 0 ? '' : 'border-r border-gray-200 dark:border-gray-700' }} {{ $loop->last ? '' : 'border-b border-gray-200 dark:border-gray-700' }} ">
                    <div :key="'{{ $key }}'" x-data="{ working: false }">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $day }}</h3>
                        </div>

                        <div class="flex items-center gap-8 mb-8">
                            {{-- Working or not --}}
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       class="sr-only peer"
                                       x-model="working"
                                       x-bind:disabled="isDisabled"
                                >
                                <div
                                    class="relative w-11 h-6 bg-gray-200 rounded-full peer peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:bg-gray-700 dark:peer-focus:ring-blue-800 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-5 after:h-5 after:transition-all peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full"></div>
                                <span class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                                      x-text="working ? '{{ __('forms.works') }}' : '{{ __('forms.does_not_work') }}'"
                                ></span>
                            </label>

                            {{-- All day --}}
                            <label class="inline-flex items-center cursor-pointer"
                                   x-bind:class="!working && 'opacity-40 pointer-events-none'"
                            >
                                <input type="checkbox"
                                       x-model="localAvailableTime[{{ $loop->index }}].allDay"
                                       x-bind:disabled="!working || isDisabled"
                                       @change="if(localAvailableTime[{{ $loop->index }}].allDay) {
                                           localAvailableTime[{{ $loop->index }}].availableStartTime = null;
                                           localAvailableTime[{{ $loop->index }}].availableEndTime = null;
                                       }"
                                       class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                />
                                <span class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    {{ __('healthcare-services.all_day') }}
                                </span>
                            </label>
                        </div>

                        {{-- Show time if working and not all day --}}
                        <div class="flex gap-4"
                             x-show="working && !localAvailableTime[{{ $loop->index }}].allDay"
                        >
                            {{-- Start --}}
                            <div class="form-group w-full">
                                <label for="availableStartTime-{{ $loop->index }}"
                                       class="label !text-xs !text-gray-500 dark:!text-gray-400">
                                    <span>{{ __('forms.opening') }}</span>
                                </label>
                                <div class="relative w-full">
                                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                             aria-hidden="true"
                                             xmlns="http://www.w3.org/2000/svg"
                                             width="24"
                                             height="24"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                        >
                                            <path stroke="currentColor"
                                                  stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                            />
                                        </svg>
                                    </div>
                                    <input type="text"
                                           class="input timepicker-uk text-gray-900 dark:text-white border-t-0 border-r-0 border-l-0 border-b border-gray-300 focus:ring-0 px-0 ps-8"
                                           placeholder="00:00"
                                           x-ref="start"
                                           id="availableStartTime-{{ $loop->index }}"
                                           @input="localAvailableTime[{{ $loop->index }}].availableStartTime = $refs.start.value ? $refs.start.value + ':00' : null"
                                           x-bind:disabled="isDisabled"
                                    />
                                </div>
                            </div>

                            {{-- End --}}
                            <div class="form-group w-full">
                                <label for="availableEndTime-{{ $loop->index }}"
                                       class="label !text-xs !text-gray-500 dark:!text-gray-400">
                                    <span>{{ __('forms.closing') }}</span>
                                </label>
                                <div class="relative w-full">
                                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                             aria-hidden="true"
                                             xmlns="http://www.w3.org/2000/svg"
                                             width="24"
                                             height="24"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                        >
                                            <path stroke="currentColor"
                                                  stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                            />
                                        </svg>
                                    </div>
                                    <input type="text"
                                           class="input timepicker-uk text-gray-900 dark:text-white border-t-0 border-r-0 border-l-0 border-b border-gray-300 focus:ring-0 px-0 ps-8"
                                           placeholder="00:00"
                                           x-ref="end"
                                           id="availableEndTime-{{ $loop->index }}"
                                           @input="localAvailableTime[{{ $loop->index }}].availableEndTime = $refs.end.value ? $refs.end.value + ':00' : null"
                                           x-bind:disabled="isDisabled"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</fieldset>
