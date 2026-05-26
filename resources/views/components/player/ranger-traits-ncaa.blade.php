@props([
    'player',
    'rangerSheet' => [],
])

@php
    /*
     * Three flex columns: Performance→Engine, K-Zone→Platoon, Adjustability→Swing.
     * Columns stretch to the same height (tallest wins, usually Adjustability). flex-1 + justify-end
     * on the lower dock pushes Engine & Platoon to the column bottom so their table feet line up with
     * the Swing pane (title + take strip only; no stat slot), which ends the third column.
     */
    $ncaaColumn = 'flex min-h-0 min-w-0 flex-1 flex-col gap-y-2.5 sm:gap-y-3 md:gap-y-3.5';
    $ncaaColumnAdjustSwing = 'flex min-h-0 min-w-0 flex-1 flex-col gap-y-2 sm:gap-y-2.5 md:gap-y-3';
    $ncaaLowerDock = 'flex min-h-0 min-w-0 flex-1 flex-col justify-end';
    $ncaaBlock = 'min-w-0 shrink-0 overflow-x-hidden pb-0.5';
@endphp

<section
    {{ $attributes->merge([
        'class' => 'flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto overscroll-y-contain',
        'aria-label' => __('Ranger Traits'),
    ]) }}
>
    <div
        class="flex w-full min-h-0 min-w-0 shrink-0 items-stretch gap-x-2.5 overflow-x-hidden pb-0.5 sm:gap-x-3 sm:pb-1 md:gap-x-4 md:pb-1.5 lg:gap-x-5 2xl:gap-x-6"
    >
        <div class="{{ $ncaaColumn }}" role="presentation">
            <div class="{{ $ncaaBlock }}" role="region" aria-label="{{ __('Performance') }}">
                @include('components.player.ranger-traits-ncaa.partials.performance', ['player' => $player, 'rangerSheet' => $rangerSheet])
            </div>
            <div class="{{ $ncaaLowerDock }}">
                <div class="{{ $ncaaBlock }}" role="region" aria-label="{{ __('Engine / Impact') }}">
                    @include('components.player.ranger-traits-ncaa.partials.engine', ['player' => $player, 'rangerSheet' => $rangerSheet])
                </div>
            </div>
        </div>
        <div class="{{ $ncaaColumn }}" role="presentation">
            <div class="{{ $ncaaBlock }}" role="region" aria-label="{{ __('K-Zone Control') }}">
                @include('components.player.ranger-traits-ncaa.partials.kzone', [
                    'player' => $player,
                    'rangerSheet' => $rangerSheet,
                ])
            </div>
            <div class="{{ $ncaaLowerDock }}">
                <div class="{{ $ncaaBlock }}" role="region" aria-label="{{ __('Platoon') }}">
                    @include('components.player.ranger-traits-ncaa.partials.platoon', ['player' => $player, 'rangerSheet' => $rangerSheet])
                </div>
            </div>
        </div>
        <div class="{{ $ncaaColumnAdjustSwing }}" role="presentation">
            <div class="{{ $ncaaBlock }}" role="region" aria-label="{{ __('Adjustability') }}">
                @include('components.player.ranger-traits-ncaa.partials.adjustability', ['player' => $player, 'rangerSheet' => $rangerSheet])
            </div>
            <div class="{{ $ncaaBlock }}" role="region" aria-label="{{ __('Swing') }}">
                @include('components.player.ranger-traits-ncaa.partials.swing', ['player' => $player, 'rangerSheet' => $rangerSheet])
            </div>
        </div>
    </div>
</section>
