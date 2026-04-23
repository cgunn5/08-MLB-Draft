@php
    use App\Support\NoteGradeInputAppearance;
    use App\Support\PlayerNoteFieldKeys;
@endphp

@php
    /** NCAA 2×3 grid: fill equal-height cell. */
    $fillGridCell = $fillGridCell ?? false;
    /** NCAA row 4: below the fold, content-sized textarea. */
    $belowFold = $belowFold ?? false;
    $fieldKey = $section['key'];
    $fieldLabel = $section['label'];
    $fieldValue = $selectedPlayer->{$fieldKey};
    $gradeAttr = PlayerNoteFieldKeys::gradeAttributeForNoteField($fieldKey, $selectedPlayer->player_pool);
    $gradeBounds = PlayerNoteFieldKeys::gradeBoundsForNoteField($fieldKey);
    $storedGradeRaw = $gradeAttr ? $selectedPlayer->{$gradeAttr} : null;
    $storedGradeInt =
        $gradeAttr && filled($storedGradeRaw) && is_numeric($storedGradeRaw) ? (int) $storedGradeRaw : null;
    $oldValues = old('values');
    $displayValue =
        is_array($oldValues) && array_key_exists($fieldKey, $oldValues) ? $oldValues[$fieldKey] : $fieldValue;
    $oldGrades = old('grades');
    $gradeInt = $storedGradeInt;
    if (is_array($oldGrades) && array_key_exists($fieldKey, $oldGrades)) {
        $g = $oldGrades[$fieldKey];
        $gradeInt = $g !== null && $g !== '' && is_numeric($g) ? (int) $g : null;
    }
    $gradeLabelShort =
        $fieldKey === 'master_take'
            ? __('Role (:min-:max)', [
                'min' => $gradeBounds['min'],
                'max' => $gradeBounds['max'],
            ])
            : __('Grade (:min-:max)', [
                'min' => $gradeBounds['min'],
                'max' => $gradeBounds['max'],
            ]);
@endphp

<section
    @class([
        'notes-grades-section-card flex min-w-0 flex-col gap-3 overflow-visible rounded-lg bg-white sm:gap-3.5 md:gap-4',
        'min-h-0 flex-1 basis-0' => ! $fillGridCell && ! $belowFold,
        'self-start min-h-0 w-full' => $fillGridCell,
        'w-full shrink-0' => $belowFold,
    ])
    aria-labelledby="notes-section-{{ $fieldKey }}-title"
>
    <div
        class="flex shrink-0 flex-col gap-3 px-3 pt-2 sm:gap-3.5 sm:px-4 sm:pt-2.5 md:gap-4"
    >
        <div class="flex min-w-0 items-center gap-4 sm:gap-5 md:gap-7 lg:gap-8">
            <div
                class="h-px min-w-[0.75rem] flex-1 bg-gray-900 sm:h-0.5 sm:min-w-[1rem]"
                aria-hidden="true"
            ></div>
            <h4
                id="notes-section-{{ $fieldKey }}-title"
                class="notes-grades-section-heading max-w-full shrink-0 text-center text-sm uppercase leading-none tracking-wide sm:text-base"
            >
                {{ $fieldLabel }}
            </h4>
            <div
                class="h-px min-w-[0.75rem] flex-1 bg-gray-900 sm:h-0.5 sm:min-w-[1rem]"
                aria-hidden="true"
            ></div>
        </div>
        @if ($gradeAttr)
            <div class="flex w-full flex-col items-center gap-1">
                <input
                    type="number"
                    name="grades[{{ $fieldKey }}]"
                    form="notes-bulk-save"
                    id="notes-grade-{{ $fieldKey }}"
                    value="{{ $gradeInt }}"
                    min="{{ $gradeBounds['min'] }}"
                    max="{{ $gradeBounds['max'] }}"
                    step="1"
                    inputmode="numeric"
                    aria-label="{{ $gradeLabelShort }}"
                    class="notes-grade-score-input h-7 w-10 rounded border px-1.5 tabular-nums shadow-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300/80 sm:w-11 sm:px-2"
                    style="{{ NoteGradeInputAppearance::inputStyle($gradeInt, $gradeBounds['min'], $gradeBounds['max']) }}"
                />
                <x-input-error :messages="$errors->get('grades.'.$fieldKey)" class="shrink-0 text-center" />
            </div>
        @endif
    </div>
    <div
        @class([
            'notes-grades-section-body flex min-h-0 min-w-0 flex-col px-3 pb-3 sm:px-4 sm:pb-4',
            'flex-1' => ! $belowFold,
        ])
    >
        <div class="notes-grades-form-grid">
            <textarea
                name="values[{{ $fieldKey }}]"
                form="notes-bulk-save"
                id="notes-edit-{{ $fieldKey }}"
                rows="1"
                class="notes-grades-textarea block rounded-md border-gray-300 font-normal normal-case shadow-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300/80 focus:ring-offset-2 focus:ring-offset-white"
            >{{ $displayValue }}</textarea>
            <div class="flex min-w-0 flex-col gap-1">
                <x-input-error :messages="$errors->get('values.'.$fieldKey)" class="shrink-0" />
            </div>
        </div>
    </div>
</section>
