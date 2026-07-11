<template x-teleport="body">
    <div
        x-show="draftMoneyEditor"
        x-cloak
        @mousedown.stop
        @click.outside="closeDraftMoneyEditor()"
        @keydown.escape.window="closeDraftMoneyEditor()"
        class="working-board-draft-money-popover working-board-annotation-popover--fixed"
        :style="draftMoneyEditorStyle()"
    >
        <div class="working-board-annotation-popover-header working-board-drafted-popover-header">
            {{ __('MARK DRAFTED') }}
        </div>
        <div class="working-board-drafted-options">
            <button
                type="button"
                class="working-board-drafted-option-btn"
                :class="isDraftedOther(draftMoneyCard()) ? 'working-board-drafted-option-btn--selected' : ''"
                @click.stop="setDraftedStatus('other')"
            >
                <span aria-hidden="true">🚫</span>
                <span>{{ __('OTHER TEAM') }}</span>
            </button>
            <button
                type="button"
                class="working-board-drafted-option-btn"
                :class="isDraftedUs(draftMoneyCard()) ? 'working-board-drafted-option-btn--selected' : ''"
                @click.stop="setDraftedStatus('us')"
            >
                <span aria-hidden="true">⭐️</span>
                <span>{{ __('TEXAS RANGERS') }}</span>
            </button>
            <button
                type="button"
                class="working-board-drafted-option-btn working-board-drafted-option-btn--clear"
                x-show="draftedStatus(draftMoneyCard())"
                @click.stop="setDraftedStatus('')"
            >
                <span aria-hidden="true">↩</span>
                <span>{{ __('REMOVE') }}</span>
            </button>
        </div>

        <div class="working-board-draft-money-divider" aria-hidden="true"></div>

        <div class="working-board-annotation-popover-header working-board-signing-bonus-popover-header">
            {{ __('REQUESTED SIGNING BONUS') }}
        </div>
        <input
            type="text"
            class="working-board-signing-bonus-input"
            x-model="draftMoneyEditor.bonusDraft"
            placeholder="{{ __('e.g. $2.5M') }}"
            x-init="$watch('draftMoneyEditor', () => $nextTick(() => $el.focus()))"
            @keydown.enter.prevent="saveDraftMoneyBonus()"
        />
        <div class="working-board-annotation-popover-actions">
            <button
                type="button"
                class="working-board-annotation-action working-board-annotation-action--clear"
                x-show="canClearDraftMoneyBonus()"
                @click.stop="clearDraftMoneyBonus()"
            >
                {{ __('CLEAR') }}
            </button>
            <button
                type="button"
                class="working-board-annotation-action working-board-annotation-action--save"
                :disabled="!canSaveDraftMoneyBonus()"
                @click.stop="saveDraftMoneyBonus()"
            >
                {{ __('SAVE') }}
            </button>
        </div>
    </div>
</template>

<template x-teleport="body">
    <div
        x-show="signingBonusTooltip"
        x-cloak
        class="working-board-signing-bonus-tooltip working-board-annotation-tooltip--fixed pointer-events-none"
        :style="signingBonusTooltipStyle()"
    >
        <div class="working-board-signing-bonus-tooltip-header">
            {{ __('REQUESTED SIGNING BONUS') }}
        </div>
        <div class="working-board-signing-bonus-tooltip-value" x-text="signingBonusTooltip?.text || '—'"></div>
    </div>
</template>
