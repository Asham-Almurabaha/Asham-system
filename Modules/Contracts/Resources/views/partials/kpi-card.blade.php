@php
    $dir = $dir ?? 'rtl';
    $wrapperClass = trim('kpi-card p-3 h-100 ' . ($wrapper_class ?? ''));
    $headerClass = trim('kpi-card__header ' . ($header_class ?? ''));
    $contentClass = trim('kpi-card__content ' . ($content_class ?? ''));
    $iconWrapperClass = trim('kpi-card__icon kpi-icon ' . ($icon_wrapper_class ?? ''));
    $iconClass = trim($icon_class ?? '');
    $titleClass = trim('kpi-card__title subnote ' . ($title_class ?? ''));
    $valueClass = trim('kpi-card__value kpi-value ' . ($value_class ?? 'fw-bold'));
    $valueSuffixClass = trim('kpi-card__value-suffix ' . ($value_suffix_class ?? 'fs-6 text-muted'));

    $metaItems = [];
    foreach ((array) ($meta ?? []) as $metaItem) {
        if (is_array($metaItem)) {
            $text = $metaItem['text'] ?? null;
            if ($text === null || $text === '') {
                continue;
            }
            $metaItems[] = [
                'text' => $text,
                'class' => trim('subnote ' . ($metaItem['class'] ?? '')),
            ];
        } elseif ($metaItem !== null && $metaItem !== '') {
            $metaItems[] = [
                'text' => $metaItem,
                'class' => 'subnote',
            ];
        }
    }

    $hintConfig = $hint ?? null;
    if (is_string($hintConfig)) {
        $hintConfig = ['text' => $hintConfig];
    }
    $hintText = $hintConfig['text'] ?? null;
    $hintIcon = $hintConfig['icon'] ?? 'bi bi-info-circle';
    $hintClass = trim('kpi-card__hint hint ' . ($hintConfig['class'] ?? ''));
    $hintPlacement = $hintConfig['placement'] ?? 'top';

    $asideConfig = is_array($aside ?? null) ? $aside : null;
    $asideMeta = [];
    if ($asideConfig && !empty($asideConfig['meta']) && is_iterable($asideConfig['meta'])) {
        foreach ($asideConfig['meta'] as $asideMetaItem) {
            if (is_array($asideMetaItem)) {
                $text = $asideMetaItem['text'] ?? null;
                if ($text === null || $text === '') {
                    continue;
                }
                $asideMeta[] = [
                    'text' => $text,
                    'class' => trim('subnote ' . ($asideMetaItem['class'] ?? '')),
                ];
            } elseif ($asideMetaItem !== null && $asideMetaItem !== '') {
                $asideMeta[] = [
                    'text' => $asideMetaItem,
                    'class' => 'subnote',
                ];
            }
        }
    }
    $asideHasContent = $asideConfig && (
        ($asideConfig['title'] ?? null) !== null || ($asideConfig['value'] ?? null) !== null ||
        ($asideConfig['html'] ?? null) !== null || !empty($asideMeta)
    );
    $asideClass = trim('kpi-card__aside ' . ($asideConfig['class'] ?? ''));
    $asideTitleClass = trim('subnote ' . ($asideConfig['title_class'] ?? ''));
    $asideValueClass = trim('kpi-card__aside-value ' . ($asideConfig['value_class'] ?? 'fw-bold'));
    $asideValueSuffixClass = trim('kpi-card__value-suffix ' . ($asideConfig['value_suffix_class'] ?? 'fs-6 text-muted'));

    $progressConfig = is_array($progress ?? null) ? $progress : null;
    $progressClass = 'progress bar-8';
    $progressBarClass = 'progress-bar';
    $progressTitle = null;
    $progressStyle = '';
    $hasProgress = false;
    if ($progressConfig) {
        $progressClass = trim('progress bar-8 ' . ($progressConfig['class'] ?? ''));
        $progressBarClass = trim('progress-bar ' . ($progressConfig['bar_class'] ?? ''));
        $progressTitle = $progressConfig['title'] ?? null;
        $progressWidth = $progressConfig['width'] ?? null;
        $progressValue = $progressConfig['value'] ?? null;
        if ($progressWidth === null && $progressValue !== null) {
            $progressWidth = is_numeric($progressValue) ? $progressValue . '%' : $progressValue;
        }
        if ($progressWidth !== null) {
            $progressStyle = 'width: ' . $progressWidth;
        }
        if (!empty($progressConfig['style'])) {
            $additional = trim($progressConfig['style']);
            $progressStyle = $progressStyle ? ($progressStyle . '; ' . $additional) : $additional;
        }
        $hasProgress = true;
    }

    $bodyContent = null;
    if (isset($body)) {
        $bodyContent = $body instanceof Illuminate\Support\HtmlString ? $body->toHtml() : $body;
    }
    $bodyClass = trim('kpi-card__body ' . ($body_class ?? ''));

    $footerConfig = $footer ?? null;
    $footerClass = 'subnote';
    $footerSplit = false;
    $footerItems = [];
    $footerHtml = null;
    if ($footerConfig instanceof Illuminate\Support\HtmlString) {
        $footerHtml = $footerConfig->toHtml();
    } elseif (is_string($footerConfig)) {
        $footerHtml = $footerConfig;
    } elseif (is_array($footerConfig)) {
        $footerSplit = (bool) ($footerConfig['split'] ?? false);
        $footerClass = trim('subnote ' . ($footerConfig['class'] ?? ''));
        if (!empty($footerConfig['items']) && is_iterable($footerConfig['items'])) {
            foreach ($footerConfig['items'] as $footerItem) {
                if (is_array($footerItem)) {
                    $text = $footerItem['text'] ?? null;
                    if ($text === null || $text === '') {
                        continue;
                    }
                    $footerItems[] = [
                        'text' => $text,
                        'class' => trim($footerItem['class'] ?? ''),
                    ];
                } elseif ($footerItem !== null && $footerItem !== '') {
                    $footerItems[] = [
                        'text' => $footerItem,
                        'class' => '',
                    ];
                }
            }
        } elseif (!empty($footerConfig['html'])) {
            $footerHtml = $footerConfig['html'];
        } elseif (!empty($footerConfig['text'])) {
            $footerHtml = $footerConfig['text'];
        }
    }
    $hasFooter = $footerHtml !== null || !empty($footerItems);

    $actionsList = [];
    foreach ((array) ($actions ?? []) as $actionItem) {
        if (!is_array($actionItem)) {
            continue;
        }

        $url = $actionItem['url'] ?? $actionItem['href'] ?? null;
        if (!is_string($url) || $url === '') {
            continue;
        }

        $attrs = [];
        if (!empty($actionItem['attrs']) && is_iterable($actionItem['attrs'])) {
            foreach ($actionItem['attrs'] as $attrKey => $attrValue) {
                if (!is_string($attrKey) || $attrKey === '' || $attrValue === null || $attrValue === '') {
                    continue;
                }
                $attrs[$attrKey] = $attrValue;
            }
        }

        $actionsList[] = [
            'url'    => $url,
            'icon'   => trim((string) ($actionItem['icon'] ?? '')),
            'label'  => $actionItem['label'] ?? null,
            'title'  => $actionItem['title'] ?? null,
            'class'  => trim('kpi-card__action ' . ($actionItem['class'] ?? '')),
            'target' => $actionItem['target'] ?? null,
            'rel'    => $actionItem['rel'] ?? null,
            'attrs'  => $attrs,
        ];
    }
    $hasActions = !empty($actionsList);
@endphp

<div class="{{ $wrapperClass }}" dir="{{ $dir }}">
    <div class="{{ $headerClass }}{{ $asideHasContent ? ' kpi-card__header--spread' : '' }}">
        <div class="kpi-card__main">
            @isset($icon)
                <div class="{{ $iconWrapperClass }}">
                    <i class="{{ trim(($icon ?? '') . ' ' . $iconClass) }}"></i>
                </div>
            @endisset

            <div class="{{ $contentClass }}">
                @isset($title)
                    <div class="{{ $titleClass }}">
                        <span>{{ $title }}</span>
                        @if($hintText)
                            <span class="{{ $hintClass }}" data-bs-toggle="tooltip" data-bs-placement="{{ $hintPlacement }}" title="{{ $hintText }}">
                                <i class="{{ $hintIcon }}"></i>
                            </span>
                        @endif
                    </div>
                @endisset

                @if(isset($description))
                    <div class="kpi-card__description subnote">{{ $description }}</div>
                @endif

                @isset($value)
                    <div class="{{ $valueClass }}">
                        <span>{{ $value }}</span>
                        @isset($value_suffix)
                            <span class="{{ $valueSuffixClass }}">{{ $value_suffix }}</span>
                        @endisset
                    </div>
                @endisset

                @foreach($metaItems as $metaItem)
                    <div class="kpi-card__meta {{ $metaItem['class'] }}">{{ $metaItem['text'] }}</div>
                @endforeach
            </div>
        </div>

        @if($asideHasContent)
            <div class="{{ $asideClass }}">
                @if(!empty($asideConfig['title']))
                    <div class="{{ $asideTitleClass }}">{{ $asideConfig['title'] }}</div>
                @endif

                @if(isset($asideConfig['value']))
                    <div class="{{ $asideValueClass }}">
                        <span>{{ $asideConfig['value'] }}</span>
                        @if(isset($asideConfig['value_suffix']))
                            <span class="{{ $asideValueSuffixClass }}">{{ $asideConfig['value_suffix'] }}</span>
                        @endif
                    </div>
                @endif

                @foreach($asideMeta as $asideMetaItem)
                    <div class="kpi-card__meta {{ $asideMetaItem['class'] }}">{{ $asideMetaItem['text'] }}</div>
                @endforeach

                @if(!empty($asideConfig['html']))
                    {!! $asideConfig['html'] !!}
                @endif
            </div>
        @endif

        @if($hasActions)
            <div class="kpi-card__actions">
                @foreach($actionsList as $action)
                    <a href="{{ $action['url'] }}"
                       class="{{ $action['class'] }}"
                       @if(!empty($action['title'])) title="{{ e($action['title']) }}" @endif
                       @if(!empty($action['target'])) target="{{ e($action['target']) }}" @endif
                       @if(!empty($action['rel'])) rel="{{ e($action['rel']) }}" @endif
                       @foreach($action['attrs'] as $attrName => $attrValue) {{ $attrName }}="{{ e($attrValue) }}" @endforeach
                    >
                        @if($action['icon'] !== '')
                            <i class="{{ $action['icon'] }}"></i>
                        @endif
                        @php($actionLabel = $action['label'])
                        @if($actionLabel instanceof Illuminate\Support\HtmlString)
                            {!! $actionLabel->toHtml() !!}
                        @elseif(is_string($actionLabel) && $actionLabel !== '')
                            <span>{{ $actionLabel }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if($bodyContent)
        <div class="{{ $bodyClass }}">{!! $bodyContent !!}</div>
    @endif

    @if($hasProgress)
        <div class="kpi-card__progress">
            <div class="{{ $progressClass }}" @if($progressTitle) title="{{ $progressTitle }}" @endif>
                <div class="{{ $progressBarClass }}" @if($progressStyle) style="{{ $progressStyle }}" @endif></div>
            </div>
        </div>
    @endif

    @if($hasFooter)
        <div class="kpi-card__footer {{ $footerClass }}{{ $footerSplit ? ' kpi-card__footer--split' : '' }}">
            @if($footerItems)
                @foreach($footerItems as $footerItem)
                    <span class="{{ $footerItem['class'] }}">{{ $footerItem['text'] }}</span>
                @endforeach
            @elseif($footerHtml !== null)
                {!! $footerHtml !!}
            @endif
        </div>
    @endif
</div>
