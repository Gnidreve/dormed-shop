<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihre Bestellbestätigung #{{ $order->id }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#111827;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:32px 16px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

    {{-- Logo --}}
    <tr>
        <td style="padding:0 0 24px 0;" align="center">
            <img src="https://dormed24.de/media/5d/8f/b3/1741900744/dormed24-logo.svg?ts=1741900744" alt="dormed24" height="36" style="height:36px;display:block;">
        </td>
    </tr>

    {{-- Card --}}
    <tr>
        <td style="background:#ffffff;border-radius:8px;border:1px solid #e5e7eb;overflow:hidden;">
            <table width="100%" cellpadding="0" cellspacing="0">

                {{-- Header --}}
                <tr>
                    <td style="padding:20px 24px;border-bottom:1px solid #f3f4f6;">
                        <p style="margin:0;font-size:13px;color:#6b7280;">Vielen Dank für Ihre Bestellung!</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:700;color:#111827;">Bestellung #{{ $order->id }}</p>
                    </td>
                </tr>

                @php $isInvoice = ($order->payment_method ?? 'invoice') === 'invoice'; @endphp

                {{-- Intro --}}
                <tr>
                    <td style="padding:16px 24px;border-bottom:1px solid #f3f4f6;background:#f9fafb;">
                        <p style="margin:0;font-size:14px;color:#374151;">
                            Hallo {{ $customer->name }},<br><br>
                            @if($isInvoice)
                                wir haben Ihre Bestellung erhalten und bitten Sie, den Gesamtbetrag per Überweisung auf folgendes Konto zu überweisen. Nach Zahlungseingang wird Ihre Bestellung umgehend bearbeitet.
                            @else
                                vielen Dank für Ihre Bestellung – Ihre Zahlung ist bei uns eingegangen. Ihre Bestellung wird nun bearbeitet und schnellstmöglich versandt.
                            @endif
                        </p>
                    </td>
                </tr>

                @if($isInvoice)
                {{-- Bankverbindung --}}
                <tr>
                    <td style="padding:20px 24px;border-bottom:1px solid #f3f4f6;">
                        <p style="margin:0 0 12px;font-size:14px;font-weight:700;color:#111827;">Bankverbindung</p>
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size:13px;color:#6b7280;padding:3px 0;width:140px;">Kontoinhaber</td>
                                <td style="font-size:13px;font-weight:600;color:#111827;padding:3px 0;">Dormed medizinische Systeme GmbH</td>
                            </tr>
                            <tr>
                                <td style="font-size:13px;color:#6b7280;padding:3px 0;">IBAN</td>
                                <td style="font-size:13px;font-weight:600;color:#111827;padding:3px 0;font-family:monospace;">DE00 0000 0000 0000 0000 00</td>
                            </tr>
                            <tr>
                                <td style="font-size:13px;color:#6b7280;padding:3px 0;">BIC</td>
                                <td style="font-size:13px;font-weight:600;color:#111827;padding:3px 0;font-family:monospace;">XXXXXXXX</td>
                            </tr>
                            <tr>
                                <td style="font-size:13px;color:#6b7280;padding:3px 0;">Bank</td>
                                <td style="font-size:13px;font-weight:600;color:#111827;padding:3px 0;">Sparkasse Unna</td>
                            </tr>
                            <tr>
                                <td style="font-size:13px;color:#6b7280;padding:3px 0;">Verwendungszweck</td>
                                <td style="font-size:13px;font-weight:700;color:#1a3a5c;padding:3px 0;">Bestellung #{{ $order->id }}</td>
                            </tr>
                        </table>
                        <p style="margin:12px 0 0;font-size:12px;color:#9ca3af;">Bitte geben Sie immer die Bestellnummer als Verwendungszweck an.</p>
                    </td>
                </tr>
                @endif

                {{-- Bestellübersicht --}}
                <tr>
                    <td style="padding:20px 24px 0;">
                        <p style="margin:0 0 16px;font-size:14px;font-weight:600;color:#111827;">Ihre Bestellung</p>
                    </td>
                </tr>

                @foreach($order->items as $i => $item)
                <tr>
                    <td style="padding:0 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="{{ $i > 0 ? 'border-top:1px solid #f3f4f6;' : '' }}padding:12px 0;">
                            <tr>
                                <td style="padding:0 12px 0 0;" valign="top">
                                    <p style="margin:0;font-size:14px;font-weight:600;color:#111827;">{{ $item->product_name }}</p>
                                    <p style="margin:2px 0 0;font-size:12px;color:#9ca3af;">Menge: {{ $item->quantity }}</p>
                                </td>
                                <td align="right" valign="top" style="white-space:nowrap;">
                                    <p style="margin:0;font-size:14px;font-weight:600;color:#111827;">{{ number_format((float)$item->unit_price * $item->quantity, 2, ',', '.') }} €</p>
                                    <p style="margin:2px 0 0;font-size:12px;color:#9ca3af;">{{ $item->quantity }} × {{ number_format((float)$item->unit_price, 2, ',', '.') }} €</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endforeach

                {{-- Divider --}}
                <tr><td style="padding:8px 24px 0;"><div style="border-top:1px solid #e5e7eb;"></div></td></tr>

                {{-- Totals --}}
                <tr>
                    <td style="padding:16px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            @php
                                $vatRate = (int) config('shop.cart.vat_rate', 19);
                                $total = (float) $order->total_amount;
                                $shipping = (float) $order->shipping_amount;
                                $subtotal = $total - $shipping;
                                $net = round($total / (1 + $vatRate / 100), 2);
                                $vat = round($total - $net, 2);
                            @endphp
                            <tr>
                                <td style="padding:4px 0;font-size:14px;color:#6b7280;">Zwischensumme</td>
                                <td align="right" style="padding:4px 0;font-size:14px;color:#374151;">{{ number_format($subtotal, 2, ',', '.') }} €*</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;font-size:14px;color:#6b7280;">Versandkosten</td>
                                <td align="right" style="padding:4px 0;font-size:14px;color:#374151;">{{ number_format($shipping, 2, ',', '.') }} €*</td>
                            </tr>
                            <tr>
                                <td style="padding-top:12px;border-top:1px solid #e5e7eb;font-size:15px;font-weight:700;color:#111827;">Gesamtbetrag</td>
                                <td align="right" style="padding-top:12px;border-top:1px solid #e5e7eb;font-size:15px;font-weight:700;color:#111827;">{{ number_format($total, 2, ',', '.') }} €*</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-top:8px;font-size:12px;color:#9ca3af;">* inkl. {{ $vatRate }}&nbsp;% MwSt. ({{ number_format($vat, 2, ',', '.') }} €)</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Lieferadresse --}}
                @if($order->shipping_address)
                <tr><td><div style="border-top:1px solid #e5e7eb;"></div></td></tr>
                <tr>
                    <td style="padding:16px 24px;">
                        <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#374151;">Lieferadresse</p>
                        @php $a = $order->shipping_address; @endphp
                        <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">
                            @if(!empty($a['company'])){{ $a['company'] }}<br>@endif
                            {{ $a['first_name'] ?? '' }} {{ $a['last_name'] ?? '' }}<br>
                            {{ $a['street'] ?? '' }} {{ $a['house_number'] ?? '' }}<br>
                            {{ $a['zip'] ?? '' }} {{ $a['city'] ?? '' }}
                        </p>
                    </td>
                </tr>
                @endif

                {{-- Footer note --}}
                <tr><td><div style="border-top:1px solid #e5e7eb;"></div></td></tr>
                <tr>
                    <td style="padding:16px 24px;" align="center">
                        <p style="margin:0;font-size:13px;color:#6b7280;">
                            Bei Fragen stehen wir Ihnen gerne zur Verfügung.<br>
                            <a href="tel:+492301188600" style="color:#1a6bbf;text-decoration:none;">02301 – 188600</a> &middot;
                            <a href="mailto:mail@dormed.de" style="color:#1a6bbf;text-decoration:none;">mail@dormed.de</a>
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td style="padding:20px 0 0;text-align:center;">
            <p style="margin:0;font-size:12px;color:#9ca3af;">
                Dormed medizinische Systeme GmbH &middot; 02301&nbsp;–&nbsp;188600
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>
