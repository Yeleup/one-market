<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('admin.resources.order.purchase_report.title') }}</title>
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
            font-size: 22px;
        }

        h2 {
            font-size: 15px;
            margin-top: 22px;
            padding-bottom: 4px;
            border-bottom: 2px solid #111827;
        }

        .summary {
            margin: 0 0 8px;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .col-total, .col-weight {
            width: 12%;
            text-align: right;
            white-space: nowrap;
        }

        .col-orders {
            width: 42%;
        }

        .order-line {
            display: block;
        }

        .order-line + .order-line {
            margin-top: 2px;
        }

        .order-ref {
            font-weight: 700;
        }

        tfoot td {
            font-weight: 700;
            background: #f9fafb;
        }

        .grand-total {
            margin-top: 18px;
            font-size: 14px;
            font-weight: 700;
        }

        .empty {
            margin-top: 16px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>{{ __('admin.resources.order.purchase_report.title') }}</h1>

    <p class="summary">
        {{ __('admin.resources.order.purchase_report.generated_at') }}:
        {{ \App\Support\Dates\DisplayDate::format($generatedAt, 'Y-m-d H:i') }}
    </p>
    <p class="summary">
        {{ __('admin.resources.order.purchase_report.orders_included') }}
        ({{ $orders->count() }}):
        {{ $orders->map(fn ($order) => '#'.$order->getKey())->implode(', ') }}
    </p>

    @forelse ($report['categories'] as $category)
        <h2>{{ $category['name'] }}</h2>

        <table>
            <thead>
                <tr>
                    <th>{{ __('admin.resources.order.purchase_report.columns.product') }}</th>
                    <th class="col-total">{{ __('admin.resources.order.purchase_report.columns.total') }}</th>
                    <th class="col-weight">{{ __('admin.resources.order.purchase_report.columns.weight') }}</th>
                    <th class="col-orders">{{ __('admin.resources.order.purchase_report.columns.by_orders') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($category['products'] as $product)
                    <tr>
                        <td>{{ $product['name'] }}</td>
                        <td class="col-total">{{ $product['total_quantity'] }}</td>
                        <td class="col-weight">{{ $product['total_weight_grams'] }} g</td>
                        <td class="col-orders">
                            @foreach ($product['lines'] as $line)
                                <span class="order-line">
                                    <span class="order-ref">#{{ $line['order_id'] }}</span>
                                    {{ $line['client_name'] }} — {{ $line['quantity'] }}
                                </span>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>{{ __('admin.resources.order.purchase_report.category_total') }}</td>
                    <td class="col-total">{{ $category['total_quantity'] }}</td>
                    <td class="col-weight">{{ $category['total_weight_grams'] }} g</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @empty
        <p class="empty">{{ __('admin.resources.order.purchase_report.empty') }}</p>
    @endforelse

    @if (! empty($report['categories']))
        <p class="grand-total">
            {{ __('admin.resources.order.purchase_report.grand_total') }}:
            {{ $report['grand_total_quantity'] }}
        </p>
    @endif
</body>
</html>
