<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class OrderPdfController extends Controller
{
    public function __invoke(Order $order): Response
    {
        $order->load([
            'client',
            'createdByUser',
            'items',
            'institution' => fn ($query) => $query->withLocalizedName(),
        ]);

        $pdf = Pdf::loadView('pdf.orders.show', [
            'order' => $order,
        ]);

        return $pdf->stream("order-{$order->getKey()}.pdf");
    }
}
