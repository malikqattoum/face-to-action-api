<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote {{ $quote_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }
        .logo-placeholder {
            width: 80px;
            height: 80px;
            background: #2563eb;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        .business-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .business-tagline {
            font-size: 12px;
            color: #666;
        }
        .quote-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
        }
        .quote-info-block {
            text-align: left;
        }
        .quote-info-block.right {
            text-align: right;
        }
        .quote-label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }
        .quote-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }
        .customer-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
        .service-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }
        .issue-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        .description-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
            margin-bottom: 20px;
            font-style: italic;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #2563eb;
            color: white;
            padding: 10px 15px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:nth-child(even) {
            background: #f8fafc;
        }
        .amount {
            text-align: right;
            font-weight: 600;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
        }
        .totals-table td {
            padding: 8px 15px;
        }
        .totals-table tr.subtotal td {
            border-top: 1px solid #e2e8f0;
        }
        .totals-table tr.total {
            background: #2563eb;
            color: white;
        }
        .totals-table tr.total td {
            font-weight: bold;
            font-size: 16px;
        }
        .notes-box {
            background: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
        }
        .notes-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 8px;
        }
        .next-steps-box {
            background: #d1fae5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .next-steps-title {
            font-weight: bold;
            color: #065f46;
            margin-bottom: 8px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #888;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-placeholder">FT</div>
        <div class="business-name">{{ $business_name }}</div>
        <div class="business-tagline">{{ $business_tagline }}</div>
    </div>

    <div class="quote-info">
        <div class="quote-info-block">
            <div class="quote-label">Quote Number</div>
            <div class="quote-value">{{ $quote_number }}</div>
        </div>
        <div class="quote-info-block right">
            <div class="quote-label">Date Issued</div>
            <div class="quote-value">{{ $date }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Customer Information</div>
        <div class="customer-name">{{ $customer_name }}</div>
        <div>
            <span class="service-badge">{{ $service_type }}</span>
            @if($issue_type)
                <span class="issue-badge">{{ $issue_type }}</span>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Service Description</div>
        <div class="description-box">{{ $issue_description }}</div>
    </div>

    @if($action_taken)
    <div class="section">
        <div class="section-title">Work Performed</div>
        <p>{{ $action_taken }}</p>
    </div>
    @endif

    @if(count($parts_line_items) > 0)
    <div class="section">
        <div class="section-title">Parts &amp; Materials</div>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="amount">Estimated Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($parts_line_items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="amount">${{ number_format($item['price'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="totals">
        <table class="totals-table">
            <tr class="subtotal">
                <td>Parts Subtotal:</td>
                <td class="amount">${{ number_format($parts_subtotal, 2) }}</td>
            </tr>
            <tr class="subtotal">
                <td>Labor Estimate:</td>
                <td class="amount">${{ number_format($labor_estimate, 2) }}</td>
            </tr>
            <tr class="total">
                <td>TOTAL ESTIMATE:</td>
                <td class="amount">${{ number_format($total_estimate, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($next_steps)
    <div class="next-steps-box">
        <div class="next-steps-title">Next Steps / Follow-up</div>
        <p>{{ $next_steps }}</p>
    </div>
    @endif

    <div class="notes-box">
        <div class="notes-title">Terms &amp; Conditions</div>
        <p>{{ $notes }}</p>
    </div>

    <div class="footer">
        <p>Generated by Face-to-Action App</p>
        <p>{{ $quote_number }} | {{ $date }}</p>
    </div>
</body>
</html>
