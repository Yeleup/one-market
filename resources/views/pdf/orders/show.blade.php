<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('admin.resources.order.model_label') }} #{{ $order->getKey() }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            line-height: 1.5;
        }

        h1, h2 {
            margin: 0 0 12px;
        }

        h1 {
            font-size: 24px;
        }

        h2 {
            font-size: 16px;
            margin-top: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .meta-table td:first-child {
            width: 32%;
            font-weight: 700;
            background: #f9fafb;
        }

        .totals {
            margin-top: 16px;
        }

        .totals td:first-child {
            font-weight: 700;
            width: 60%;
        }
    </style>
</head>
<body>
    <h1>{{ __('admin.resources.order.model_label') }} #{{ $order->getKey() }}</h1>

    <table class="meta-table">
        <tbody>
            <tr>
                <td>{{ __('admin.common.fields.status') }}</td>
                <td>{{ $order->status?->getLabel() ?? $order->status?->value }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.source') }}</td>
                <td>{{ $order->source?->getLabel() ?? $order->source?->value }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.client') }}</td>
                <td>{{ $order->client?->full_name ?? '—' }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.login') }}</td>
                <td>{{ $order->client?->login ?? '—' }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.institution') }}</td>
                <td>{{ $order->institution?->localized_name ?? '—' }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.recipient') }}</td>
                <td>{{ $order->recipient_full_name ?: '—' }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.bin') }}</td>
                <td>{{ $order->recipient_bin_value ?? '—' }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.created_by') }}</td>
                <td>{{ $order->createdByUser?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.created_at') }}</td>
                <td>{{ $order->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.placed_at') }}</td>
                <td>{{ $order->placed_at?->format('Y-m-d H:i') ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <h2>{{ __('admin.common.fields.products') }}</h2>

    <table>
        <thead>
            <tr>
                <th>{{ __('admin.common.fields.product_name') }}</th>
                <th>{{ __('admin.common.fields.quantity') }}</th>
                <th>{{ __('admin.common.fields.price_bonus') }}</th>
                <th>{{ __('admin.common.fields.weight') }}</th>
                <th>{{ __('admin.common.fields.line_total_bonus') }}</th>
                <th>{{ __('admin.common.fields.line_total_weight') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->price_bonus }}</td>
                    <td>{{ $item->weight_grams }} g</td>
                    <td>{{ $item->line_total_bonus }}</td>
                    <td>{{ $item->line_total_weight_grams }} g</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('admin.resources.order.fields.items_hint') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tbody>
            <tr>
                <td>{{ __('admin.common.fields.total_bonus') }}</td>
                <td>{{ $order->total_bonus }}</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.total_weight') }}</td>
                <td>{{ $order->total_weight_grams }} g</td>
            </tr>
            <tr>
                <td>{{ __('admin.common.fields.bonus_reserved') }}</td>
                <td>{{ $order->reserved_bonus_amount }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
