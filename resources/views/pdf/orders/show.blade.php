<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ Str::ucfirst(__('admin.resources.order.model_label')) }} #{{ $order->getKey() }}</title>
    <style>
        @page {
            margin: 10mm 10mm 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111827;
            line-height: 1.25;
            margin: 0;
        }

        h1 {
            font-size: 13px;
            margin: 0;
        }

        h2 {
            font-size: 10px;
            margin: 10px 0 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 2px 4px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            font-weight: 700;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .header {
            border: 0;
            margin-bottom: 6px;
        }

        .header td {
            border: 0;
            padding: 0;
        }

        .header .date {
            text-align: right;
            vertical-align: bottom;
        }

        .meta-table th {
            width: 15%;
            white-space: nowrap;
        }

        .meta-table td {
            width: 35%;
        }

        .num {
            text-align: right;
            white-space: nowrap;
            width: 10%;
        }

        tfoot td {
            font-weight: 700;
            background: #f9fafb;
        }

        .signatures {
            margin-top: 22px;
            page-break-inside: avoid;
        }

        .signatures td {
            border: 0;
            padding: 0;
            white-space: nowrap;
        }

        .signatures .sig-label {
            width: 1%;
            padding-right: 4px;
        }

        .signatures .sig-line {
            border-bottom: 1px solid #111827;
            width: 32%;
        }

        .signatures .sig-gap {
            width: 6%;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>{{ Str::ucfirst(__('admin.resources.order.model_label')) }} #{{ $order->getKey() }}</h1>
            </td>
            <td class="date">
                {{ __('admin.common.fields.created_at') }}:
                {{ \App\Support\Dates\DisplayDate::format($order->created_at, 'Y-m-d H:i') ?? '—' }}
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tbody>
            <tr>
                <th>{{ __('admin.common.fields.supplier') }}</th>
                <td>{{ config('company.supplier.name') }}</td>
                <th>{{ __('admin.common.fields.bin') }}</th>
                <td>{{ config('company.supplier.bin') }}</td>
            </tr>
            <tr>
                <th>{{ __('admin.common.fields.status') }}</th>
                <td>{{ $order->status?->getLabel() ?? $order->status?->value }}</td>
                <th>{{ __('admin.common.fields.institution') }}</th>
                <td>{{ $order->institution?->localized_name ?? '—' }}</td>
            </tr>
            <tr>
                <th>{{ __('admin.common.fields.client') }}</th>
                <td>{{ $order->client?->full_name ?? '—' }}</td>
                <th>{{ __('admin.common.fields.bin') }}</th>
                <td>{{ $order->client?->bin ?? '—' }}</td>
            </tr>
            <tr>
                <th>{{ __('admin.common.fields.recipient') }}</th>
                <td>{{ $order->recipient_full_name ?: '—' }}</td>
                <th>{{ __('admin.common.fields.recipient_bin') }}</th>
                <td>{{ $order->recipient_bin_value ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <h2>{{ __('admin.common.fields.products') }}</h2>

    <table>
        <thead>
            <tr>
                <th>{{ __('admin.common.fields.product_name') }}</th>
                <th class="num">{{ __('admin.common.fields.quantity') }}</th>
                <th class="num">{{ __('admin.common.fields.price_bonus') }}</th>
                <th class="num">{{ __('admin.common.fields.weight') }}</th>
                <th class="num">{{ __('admin.common.fields.line_total_bonus') }}</th>
                <th class="num">{{ __('admin.common.fields.line_total_weight') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ $item->price_bonus }}</td>
                    <td class="num">{{ $item->weight_grams }} g</td>
                    <td class="num">{{ $item->line_total_bonus }}</td>
                    <td class="num">{{ $item->line_total_weight_grams }} g</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('admin.resources.order.fields.items_hint') }}</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">{{ __('admin.common.fields.total_bonus') }} / {{ __('admin.common.fields.total_weight') }}</td>
                <td class="num">{{ $order->total_bonus }}</td>
                <td class="num">{{ $order->total_weight_grams }} g</td>
            </tr>
            <tr>
                <td colspan="4">{{ __('admin.common.fields.bonus_reserved') }}</td>
                <td class="num" colspan="2">{{ $order->reserved_bonus_amount }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signatures">
        <tbody>
            <tr>
                <td class="sig-label">{{ __('admin.resources.order.pdf.released_by') }}</td>
                <td class="sig-line">&nbsp;</td>
                <td class="sig-gap">&nbsp;</td>
                <td class="sig-label">{{ __('admin.resources.order.pdf.received_by') }}</td>
                <td class="sig-line">&nbsp;</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
