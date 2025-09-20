@props([
    'striped' => false,
    'bordered' => false,
    'hover' => true,
    'small' => false,
    'responsive' => true,
    'containerClass' => 'table-responsive',
    'headClass' => '',
    'bodyClass' => '',
    'footClass' => '',
])

@php
    $tableClasses = ['table', 'align-middle', 'mb-0'];

    if ($hover) {
        $tableClasses[] = 'table-hover';
    }

    if ($striped) {
        $tableClasses[] = 'table-striped';
    }

    if ($bordered) {
        $tableClasses[] = 'table-bordered';
    }

    if ($small) {
        $tableClasses[] = 'table-sm';
    }
@endphp

@if ($responsive)
<div class="{{ trim($containerClass) }}">
@endif
    <table {{ $attributes->class($tableClasses) }}>
        @isset($head)
            <thead class="{{ trim($headClass) }}">
                {{ $head }}
            </thead>
        @endisset

        <tbody class="{{ trim($bodyClass) }}">
            {{ $slot }}
        </tbody>

        @isset($footer)
            <tfoot class="{{ trim($footClass) }}">
                {{ $footer }}
            </tfoot>
        @endisset
    </table>
@if ($responsive)
</div>
@endif
