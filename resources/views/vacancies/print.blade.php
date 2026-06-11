<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Заявка на подбор персонала{{ $vacancy->position ? ' — '.$vacancy->position->name : '' }}</title>
    <style>
        /* 1:1 render of «Заявка_на_подбор_персонала_Финал.docx»:
           A4, поля 19 мм (1080 twips), Times New Roman, навигационный синий
           #0F3D5C, ячейки-лейблы #E8F1F8, ячейки значений #F3F7FB. */
        :root {
            --navy: #0f3d5c;
            --label-bg: #e8f1f8;
            --value-bg: #f3f7fb;
            --line: #c9d7e2;
            --ink: #222222;
            --muted: #6b7c8c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4;
            margin: 19mm;
        }

        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            color: var(--ink);
            background: #e5e7eb;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            padding: 19mm;
            padding-bottom: 26mm;
            background: #ffffff;
            box-shadow: 0 6px 32px rgba(15, 61, 92, 0.18);
        }

        /* ── Шапка ───────────────────────────────────────────────────────── */
        .doc-header {
            display: table;
            width: 100%;
            border-bottom: 2.5pt solid var(--navy);
            padding-bottom: 8pt;
        }

        .doc-header__brand,
        .doc-header__approve {
            display: table-cell;
            vertical-align: bottom;
        }

        .doc-header__brand {
            width: 49%;
            padding-right: 12pt;
        }

        .doc-header__approve {
            width: 51%;
            border-left: 1.5pt solid var(--navy);
            padding-left: 12pt;
            text-align: right;
        }

        .brand-name {
            color: var(--navy);
            font-size: 13.5pt;
            font-weight: bold;
        }

        .brand-sub {
            color: var(--muted);
            font-size: 8.5pt;
            margin-top: 2pt;
        }

        .approve-title {
            color: var(--navy);
            font-size: 10pt;
            font-weight: bold;
        }

        .approve-line {
            font-size: 8.5pt;
            line-height: 1.35;
        }

        .approve-sign {
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 5pt;
        }

        .approve-date {
            font-size: 9.5pt;
            margin-top: 2pt;
        }

        /* ── Заголовок ───────────────────────────────────────────────────── */
        .doc-title {
            text-align: center;
            color: var(--navy);
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 0.04em;
            margin-top: 16pt;
        }

        .doc-subtitle {
            text-align: center;
            color: var(--muted);
            font-style: italic;
            font-size: 10.5pt;
            margin-top: 3pt;
            margin-bottom: 12pt;
        }

        /* ── Секции ──────────────────────────────────────────────────────── */
        .section-bar {
            background: var(--navy);
            color: #ffffff;
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 4.5pt 8pt;
            margin-top: 10pt;
            page-break-inside: avoid;
            page-break-after: avoid;
        }

        .section-bar .num { display: inline-block; min-width: 14pt; }

        table.form {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.form td {
            border: 0.75pt solid var(--line);
            padding: 5pt 8pt;
            vertical-align: middle;
            page-break-inside: avoid;
        }

        td.lbl {
            width: 31.7%;
            background: var(--label-bg);
            color: var(--navy);
            font-weight: bold;
            font-size: 9.5pt;
            line-height: 1.3;
        }

        td.val {
            background: var(--value-bg);
            font-size: 10pt;
            line-height: 1.45;
        }

        td.val.tall { height: 44pt; vertical-align: top; }
        td.val.xtall { height: 110pt; vertical-align: top; }

        .pre { white-space: pre-line; }

        .placeholder {
            color: var(--muted);
            font-style: italic;
        }

        /* ── Чекбоксы как в бланке ───────────────────────────────────────── */
        .opt {
            display: inline-block;
            margin-right: 16pt;
            white-space: nowrap;
        }

        .opt:last-child { margin-right: 0; }

        .cb {
            display: inline-block;
            width: 9pt;
            height: 9pt;
            border: 0.9pt solid #3c4f5e;
            vertical-align: -1pt;
            margin-right: 4pt;
            text-align: center;
            line-height: 8.5pt;
            font-size: 8.5pt;
        }

        .opt.on { font-weight: bold; color: var(--navy); }

        .opt.on .cb {
            background: var(--navy);
            border-color: var(--navy);
            color: #ffffff;
        }

        .optline { margin-top: 2pt; }
        .optline:first-child { margin-top: 0; }

        .fill-line {
            border-bottom: 0.75pt solid #3c4f5e;
            display: inline-block;
            min-width: 90pt;
            padding: 0 4pt;
        }

        /* ── Согласование ────────────────────────────────────────────────── */
        table.sign {
            width: 94%;
            margin: 10pt 0 0 4%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        table.sign td {
            vertical-align: top;
            padding: 2pt 14pt 2pt 0;
        }

        table.sign td + td {
            border-left: 0.75pt solid var(--line);
            padding-left: 18pt;
        }

        .sign-role {
            color: var(--navy);
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 12pt;
        }

        .sign-line { font-size: 11pt; }

        .sign-caption {
            color: var(--muted);
            font-size: 10pt;
            margin-bottom: 8pt;
        }

        .sign-date { font-size: 11pt; }

        .appendix {
            color: var(--muted);
            font-style: italic;
            font-size: 7.5pt;
            margin-top: 14pt;
        }

        /* ── Футер ───────────────────────────────────────────────────────── */
        .doc-footer {
            display: none;
        }

        /* ── Панель (только экран) ───────────────────────────────────────── */
        .toolbar {
            position: fixed;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
            z-index: 10;
        }

        .toolbar button {
            font-family: inherit;
            font-size: 14px;
            padding: 10px 22px;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            background: var(--navy);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(15, 61, 92, 0.35);
        }

        .toolbar button.ghost {
            background: #ffffff;
            color: var(--navy);
        }

        @media print {
            body { background: none; }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .toolbar { display: none; }

            .doc-footer {
                display: block;
                position: fixed;
                bottom: -12mm;
                left: 0;
                right: 0;
                border-top: 0.5pt solid var(--line);
                padding-top: 3pt;
                text-align: center;
                color: var(--muted);
                font-size: 8pt;
            }
        }
    </style>
</head>
<body>
@php
    /** @var \App\Models\Vacancy $vacancy */
    $known = config('hiring.known_languages');
    $storedLanguages = $vacancy->languages->pluck('name')->all();
    $otherLanguage = collect($storedLanguages)->first(fn (string $name) => ! in_array($name, $known, true));
    $opt = fn (bool $on, string $label) => '<span class="opt'.($on ? ' on' : '').'"><span class="cb">'.($on ? '✓' : '').'</span>'.e($label).'</span>';
@endphp

<div class="toolbar">
    <button type="button" class="ghost" onclick="window.close()">Закрыть</button>
    <button type="button" onclick="window.print()">Печать</button>
</div>

<div class="doc-footer">{{ config('hiring.organization_footer') }}&nbsp;&nbsp;·&nbsp;&nbsp;{{ config('hiring.department') }}</div>

<div class="sheet">
    <!-- Шапка -->
    <div class="doc-header">
        <div class="doc-header__brand">
            <div class="brand-name">{{ config('hiring.organization') }}</div>
            <div class="brand-sub">{{ config('hiring.department') }}</div>
        </div>
        <div class="doc-header__approve">
            <div class="approve-title">«УТВЕРЖДАЮ»</div>
            <div class="approve-line">{!! implode('<br>', array_map('e', explode('|', config('hiring.approver_position')))) !!}</div>
            <div class="approve-sign">____________ / {{ config('hiring.approver_name') }}</div>
            <div class="approve-date">«____» ______________ 20____ г.</div>
        </div>
    </div>

    <div class="doc-title">ЗАЯВКА НА ПОДБОР ПЕРСОНАЛА</div>
    <div class="doc-subtitle">описание вакансии: заполняется руководителем подразделения</div>

    <!-- 1. Информация о вакансии -->
    <div class="section-bar"><span class="num">1</span>Информация о вакансии</div>
    <table class="form">
        <tr>
            <td class="lbl">Должность (позиция)</td>
            <td class="val">{{ $vacancy->position?->name }}</td>
        </tr>
        <tr>
            <td class="lbl">Структурное подразделение</td>
            <td class="val">{{ collect([$vacancy->branch?->name, $vacancy->department?->name])->filter()->implode(' / ') }}</td>
        </tr>
        <tr>
            <td class="lbl">Место деятельности / локация</td>
            <td class="val">{{ $vacancy->location }}</td>
        </tr>
        <tr>
            <td class="lbl">Количество вакансий</td>
            <td class="val">{{ $vacancy->openings }}</td>
        </tr>
        <tr>
            <td class="lbl">Непосредственный руководитель</td>
            <td class="val">{{ $vacancy->supervisor }}</td>
        </tr>
    </table>

    <!-- 2. Требования к кандидату -->
    <div class="section-bar"><span class="num">2</span>Требования к кандидату</div>
    <table class="form">
        <tr>
            <td class="lbl">Образование</td>
            <td class="val">
                @foreach (\App\Enums\Education::cases() as $case)
                    {!! $opt($vacancy->education === $case, $case->label()) !!}
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="lbl">Опыт работы</td>
            <td class="val">
                @foreach (\App\Enums\Experience::cases() as $case)
                    {!! $opt($vacancy->experience === $case, $case->label()) !!}
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="lbl">Знание языков</td>
            <td class="val">
                @foreach ($known as $language)
                    {!! $opt(in_array($language, $storedLanguages, true), $language) !!}
                @endforeach
                <span class="opt{{ $otherLanguage ? ' on' : '' }}"><span class="cb">{{ $otherLanguage ? '✓' : '' }}</span>Другой: @if ($otherLanguage)<span class="fill-line">{{ $otherLanguage }}</span>@else __________ @endif</span>
            </td>
        </tr>
        <tr>
            <td class="lbl">Ключевые навыки и знание программ</td>
            <td class="val tall pre">{{ $vacancy->skills }}</td>
        </tr>
        <tr>
            <td class="lbl">Дополнительные требования к кандидату</td>
            <td class="val tall pre">{{ $vacancy->requirements }}</td>
        </tr>
    </table>

    <!-- 3. Должностные обязанности -->
    <div class="section-bar"><span class="num">3</span>Должностные обязанности</div>
    <table class="form">
        <tr>
            <td class="lbl" style="vertical-align: middle;">Основные обязанности</td>
            <td class="val xtall pre">{{ $vacancy->responsibilities }}</td>
        </tr>
    </table>

    <!-- 4. Условия работы -->
    <div class="section-bar"><span class="num">4</span>Условия работы</div>
    <table class="form">
        <tr>
            <td class="lbl">Тип занятости</td>
            <td class="val">
                @foreach (\App\Enums\VacancyEmploymentType::cases() as $case)
                    {!! $opt($vacancy->employment_type === $case, $case->label()) !!}
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="lbl">График работы</td>
            <td class="val">
                <div class="optline">{!! $opt($vacancy->schedule_type === \App\Enums\ScheduleType::STANDARD, '5/2, 08:00–17:00') !!}</div>
                <div class="optline">
                    <span class="opt{{ $vacancy->schedule_type === \App\Enums\ScheduleType::OTHER ? ' on' : '' }}"><span class="cb">{{ $vacancy->schedule_type === \App\Enums\ScheduleType::OTHER ? '✓' : '' }}</span>Иной (сменный, 2/2, дежурство) — укажите:</span>
                    @if ($vacancy->schedule_type === \App\Enums\ScheduleType::OTHER && $vacancy->schedule_other)
                        <span class="fill-line">{{ $vacancy->schedule_other }}</span>
                    @else
                        ________________________________
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td class="lbl">Формат работы</td>
            <td class="val">
                @foreach (\App\Enums\WorkFormat::cases() as $case)
                    {!! $opt($vacancy->work_format === $case, $case->label()) !!}
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="lbl">Предполагаемый уровень дохода</td>
            <td class="val">
                @if ($vacancy->salary !== null)
                    {{ number_format($vacancy->salary, 0, ',', ' ') }} сомонӣ
                @else
                    <span class="placeholder">оклад / диапазон, по согласованию с HR</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">Испытательный срок</td>
            <td class="val">
                @foreach (\App\Enums\Probation::cases() as $case)
                    @if ($case === \App\Enums\Probation::OTHER)
                        <span class="opt{{ $vacancy->probation === $case ? ' on' : '' }}"><span class="cb">{{ $vacancy->probation === $case ? '✓' : '' }}</span>Иное: @if ($vacancy->probation === $case && $vacancy->probation_other)<span class="fill-line">{{ $vacancy->probation_other }}</span>@else ________ @endif</span>
                    @else
                        {!! $opt($vacancy->probation === $case, $case->label()) !!}
                    @endif
                @endforeach
            </td>
        </tr>
    </table>

    <!-- 5. Причина открытия позиции и сроки -->
    <div class="section-bar"><span class="num">5</span>Причина открытия позиции и сроки</div>
    <table class="form">
        <tr>
            <td class="lbl">Причина открытия позиции</td>
            <td class="val">
                <div class="optline">
                    {!! $opt($vacancy->opening_reason === \App\Enums\OpeningReason::EXPANSION, 'Расширение штата') !!}
                    {!! $opt($vacancy->opening_reason === \App\Enums\OpeningReason::NEW_POSITION, 'Новая позиция') !!}
                </div>
                <div class="optline">{!! $opt($vacancy->opening_reason === \App\Enums\OpeningReason::REPLACEMENT, 'Замена уволенного сотрудника') !!}</div>
                <div class="optline">{!! $opt($vacancy->opening_reason === \App\Enums\OpeningReason::MATERNITY, 'Декретная ставка / временное замещение') !!}</div>
            </td>
        </tr>
        <tr>
            <td class="lbl">Приоритет / срочность</td>
            <td class="val">
                @foreach (\App\Enums\VacancyPriority::cases() as $case)
                    {!! $opt($vacancy->priority === $case, $case->label()) !!}
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="lbl">Дата подачи заявки</td>
            <td class="val">{{ $vacancy->opened_at?->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td class="lbl">Планируемая дата закрытия вакансии (Дедлайн):</td>
            <td class="val">{{ $vacancy->deadline?->format('d.m.Y') }}</td>
        </tr>
    </table>

    <!-- 6. Согласование заявки -->
    <div class="section-bar"><span class="num">6</span>Согласование заявки</div>
    <table class="sign">
        <tr>
            <td>
                <div class="sign-role">Инициатор заявки</div>
                <div class="sign-line">______________________________</div>
                <div class="sign-caption">подпись / Ф.И.О.{{ $vacancy->creator ? ' ('.$vacancy->creator->name.')' : '' }}</div>
                <div class="sign-date">«____» ____________ 20____ г.</div>
            </td>
            <td>
                <div class="sign-role">Согласовано (HR-менеджер)</div>
                <div class="sign-line">______________________________</div>
                <div class="sign-caption">подпись / Ф.И.О.</div>
                <div class="sign-date">«____» ____________ 20____ г.</div>
            </td>
        </tr>
    </table>

    <div class="appendix">{{ config('hiring.appendix') }}</div>
</div>
</body>
</html>
