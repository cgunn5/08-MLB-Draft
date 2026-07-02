<template x-teleport="body">
    <div
        x-show="annotationEditor"
        x-cloak
        @mousedown.stop
        @click.outside="closeAnnotationEditor()"
        @keydown.escape.window="closeAnnotationEditor()"
        class="working-board-annotation-popover working-board-annotation-popover--fixed"
        :style="annotationEditorStyle()"
    >
        <div class="working-board-annotation-popover-header">
            {{ __('Add note') }}
        </div>
        <div class="working-board-annotation-type-grid">
            <template x-for="ann in annotationTypes" :key="'pick-' + (annotationEditor?.boardType ?? '') + '-' + (annotationEditor?.rk ?? '') + '-' + (annotationEditor?.idx ?? '') + '-' + ann.key">
                <button
                    type="button"
                    class="working-board-annotation-type-btn"
                    :class="annotationTypeIsSelected(ann.key) ? 'working-board-annotation-type-btn--selected' : ''"
                    @click.stop="selectAnnotationType(ann.key)"
                >
                    <span aria-hidden="true" x-text="ann.icon"></span>
                    <span x-text="ann.label"></span>
                </button>
            </template>
        </div>
        <div x-show="annotationEditor?.key" x-cloak>
            <textarea
                class="working-board-annotation-textarea"
                rows="3"
                x-model="annotationEditor.draft"
                :placeholder="annotationEditor?.label ?? ''"
                x-init="$watch('annotationEditor?.key', () => $nextTick(() => $el.focus()))"
                @keydown.meta.enter.prevent="saveAnnotationEditor()"
                @keydown.ctrl.enter.prevent="saveAnnotationEditor()"
            ></textarea>
            <div class="working-board-annotation-popover-actions">
                <button
                    type="button"
                    class="working-board-annotation-action working-board-annotation-action--clear"
                    x-show="canClearAnnotationEditor()"
                    @click.stop="clearAnnotationEditor()"
                >
                    {{ __('Clear') }}
                </button>
                <button
                    type="button"
                    class="working-board-annotation-action working-board-annotation-action--save"
                    :disabled="!canSaveAnnotationEditor()"
                    @click.stop="saveAnnotationEditor()"
                >
                    {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</template>

<template x-teleport="body">
    <div
        x-show="annotationTooltip"
        x-cloak
        class="working-board-annotation-tooltip working-board-annotation-tooltip--fixed pointer-events-none"
        :style="annotationTooltipStyle()"
    >
        <table class="working-board-annotation-tooltip-table">
            <tbody>
                <template x-for="row in annotationTooltip?.rows ?? []" :key="row.label">
                    <tr>
                        <th scope="row" x-text="row.label"></th>
                        <td x-text="row.text || '—'"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>
