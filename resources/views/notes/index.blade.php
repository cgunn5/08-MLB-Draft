<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('NOTE INPUT') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- overflow-visible so the player combobox popover is not clipped --}}
            <div class="relative overflow-visible bg-white shadow-sm sm:rounded-lg">
                <div class="space-y-4 p-6 text-gray-900">
                    <div class="flex justify-center">
                        <div class="flex w-full max-w-xl flex-wrap items-end justify-center gap-3 sm:justify-between">
                            <div class="min-w-0 w-full sm:flex-1">
                                <x-notes.player-combobox
                                    :notes-combobox-players="$notesComboboxPlayers"
                                    :selected-player="$selectedPlayer"
                                />
                            </div>
                            @if ($selectedPlayer)
                                <a
                                    href="{{ route('notes.index') }}"
                                    class="shrink-0 self-end text-sm font-medium text-gray-600 hover:text-gray-900"
                                >
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    @if ($selectedPlayer)
                        @php $notesAutofocusPending = true; @endphp
                        <div class="space-y-4 border-t border-gray-100 pt-4">
                            @foreach ($noteSections as $section)
                                @php
                                    $fieldKey = $section['key'];
                                    $fieldLabel = $section['label'];
                                    $fieldValue = $selectedPlayer->{$fieldKey};
                                    $hasSavedText = filled($fieldValue);
                                    $isExplicitEdit = request('edit') === $fieldKey;
                                    $failedThisSection = $errors->any() && (string) old('field') === $fieldKey;
                                    $showEditor = ! $hasSavedText || $isExplicitEdit || $failedThisSection;
                                    $isReadOnlyDisplay = $hasSavedText && ! $isExplicitEdit && ! $failedThisSection;
                                    $autofocusTextarea = $notesAutofocusPending && $showEditor && ! $hasSavedText;
                                    if ($autofocusTextarea) {
                                        $notesAutofocusPending = false;
                                    }
                                @endphp
                                <section
                                    class="overflow-hidden rounded-lg border border-gray-200 bg-white"
                                    aria-labelledby="notes-section-{{ $fieldKey }}-title"
                                >
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-2 rounded-t-lg px-4 py-3"
                                        style="background-color: #e0f2fe; border-bottom: 1px solid #7dd3fc;"
                                    >
                                        <h4
                                            id="notes-section-{{ $fieldKey }}-title"
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ $fieldLabel }}
                                        </h4>
                                        @if ($isReadOnlyDisplay)
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a
                                                    href="{{ route('notes.index', ['player' => $selectedPlayer->id, 'edit' => $fieldKey]) }}"
                                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                                >
                                                    {{ __('Edit') }}
                                                </a>
                                                <form
                                                    method="POST"
                                                    action="{{ route('notes.destroy-section') }}"
                                                    class="inline"
                                                    onsubmit="return confirm(@js(__('Remove this note?')));"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="player_id" value="{{ $selectedPlayer->id }}" />
                                                    <input type="hidden" name="player_pool" value="{{ $selectedPlayer->player_pool }}" />
                                                    <input type="hidden" name="field" value="{{ $fieldKey }}" />
                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                                    >
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-4">
                                        @if ($showEditor)
                                            <form method="POST" action="{{ route('notes.update-section') }}" class="space-y-3">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="player_id" value="{{ $selectedPlayer->id }}" />
                                                <input type="hidden" name="player_pool" value="{{ $selectedPlayer->player_pool }}" />
                                                <input type="hidden" name="field" value="{{ $fieldKey }}" />
                                                <textarea
                                                    name="value"
                                                    id="notes-edit-{{ $fieldKey }}"
                                                    rows="5"
                                                    class="mt-0 block w-full rounded-md border-gray-300 text-sm font-normal normal-case shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    @if ($autofocusTextarea) autofocus @endif
                                                >{{ old('field') === $fieldKey ? old('value', $fieldValue) : $fieldValue }}</textarea>
                                                <x-input-error :messages="$errors->get('value')" class="mt-1" />
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                                                    @if ($hasSavedText && $showEditor)
                                                        <a
                                                            href="{{ route('notes.index', ['player' => $selectedPlayer->id]) }}"
                                                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                                        >
                                                            {{ __('Cancel') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </form>
                                        @else
                                            <div
                                                class="whitespace-pre-wrap text-sm font-normal normal-case leading-relaxed text-gray-900"
                                            >
                                                {{ $fieldValue }}
                                            </div>
                                        @endif
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
