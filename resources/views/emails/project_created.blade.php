<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h2, h4 {
            color: #333;
        }

        p {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
        }

        .summary-table, .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th, .table td, .summary-table td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .table th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: 600;
        }

        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .summary-table td {
            border: none;
        }

        .summary-table tr:last-child td {
            font-weight: 600;
        }

        .table td {
            font-size: 14px;
            color: #555;
        }

        .table th, .summary-table td {
            border-top: 1px solid #ddd;
        }

        .table td {
            border-bottom: 1px solid #ddd;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #888;
            text-align: center;
        }

        .footer a {
            color: #007BFF;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Project: {{ $project->name }}</h2>
        <p>Dear {{ $project->customer->name }},</p>
        <p>Your project has been successfully created. Below are the details for your reference:</p>

        <h4>Project Information</h4>
        <table class="summary-table">
            <tr><td><strong>Title:</strong></td><td>{{ $project->name }}</td></tr>
            <tr><td><strong>Description:</strong></td><td>{{ $project->description }}</td></tr>
            <tr><td><strong>Currency:</strong></td><td>{{ isset($project->currencyr->id) ? ($project->currencyr->currency . ' - ' . $project->currencyr->currency_symbol . ' - ' . $project->currencyr->name) : '-' }}</td></tr>
            <tr><td><strong>Tax:</strong></td><td>{{ $project->local_tax }}%</td></tr>
            <tr><td><strong>Payment Type:</strong></td><td>{{ $project->payment_type == 1 ? 'Cash' : 'Stripe' }}</td></tr>
            <tr><td><strong>Payment Collection Type:</strong></td><td>{{ $project->payment_collection_type == 1 ? 'Installment' : 'Full' }}</td></tr>
            {{-- <tr><td><strong>Start Date:</strong></td><td>{{ $project->start_date }}</td></tr>
            <tr><td><strong>End Date:</strong></td><td>{{ $project->end_date }}</td></tr> --}}
        </table>

        <h4>Line Items</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Serial #</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($project->modules as $item)
                <tr>
                    <td>{{ $item->serial_number }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h4>Installments</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($project->invoices as $i => $inv)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $inv->description }}</td>
                    <td>{{ date('d-m-Y', strtotime($inv->due_date)) }}</td>
                    <td>{{ number_format($inv->amount, 2) }}</td>
                    <td>
                        @php $statuses = [0=>'Pending',1=>'Completed',2=>'Failed']; @endphp
                        {{ $statuses[$inv->status] ?? '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h4>Summary</h4>
        <table class="summary-table">
            <tr><td><strong>Subtotal:</strong></td><td>{{ number_format($project->sub_total, 2) }}</td></tr>
            <tr><td><strong>Discount:</strong></td><td>{{ number_format($project->discount, 2) }}</td></tr>
            <tr><td><strong>VAT (Tax):</strong></td><td>{{ number_format($project->vat, 2) }}</td></tr>
            <tr><td><strong>Total:</strong></td><td>{{ number_format($project->grand_total, 2) }}</td></tr>
        </table>

        <p>Thank you. If you have any questions, feel free to reach out.</p>

        <strong>
            {{ \App\Helpers\Helper::title() }}
        </strong>
    </div>

    <div class="footer">
        <p>For any inquiries, please <a href="mailto:raincreatives@gmail.com">Contact Support</a>.</p>
    </div>
</body>
</html>
