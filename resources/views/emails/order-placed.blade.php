<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmation</title>
</head>
<body>
    <h1>Order Confirmation #{{ $order->id }}</h1>

    <p>Hi {{ $order->user->name }},</p>

    <p>Thank you for your order. Here are the details:</p>

    <p><strong>Status:</strong> {{ $order->status }}</p>
    <p><strong>Total:</strong> ${{ number_format($order->total_amount, 2) }}</p>

    <h2>Items</h2>
    <ul>
        @foreach ($order->items as $item)
            <li>
                {{ $item->product->name }} —
                Qty: {{ $item->quantity }},
                Price: ${{ number_format($item->unit_price, 2) }},
                Subtotal: ${{ number_format($item->subtotal, 2) }}
            </li>
        @endforeach
    </ul>

    <p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
