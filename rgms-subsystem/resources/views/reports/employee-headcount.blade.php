<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UIMP - Employee Headcount Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        @page {
            margin: 40px 30px;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e1b4b;
        }
        .header-subtitle {
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .report-table th {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #4f46e5;
        }
        .report-table td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
        }
        .report-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
        .rtl {
            direction: rtl;
            font-family: 'DejaVu Sans', sans-serif;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none;">
                    <div class="header-title">Employee Headcount Statistics</div>
                    <div class="header-subtitle">UIMP Staff Headcount Summary by Department</div>
                </td>
                <td style="text-align: right; border: none; font-size: 10px; color: #4b5563;">
                    <strong>Generated At:</strong> {{ $generatedAt }}
                </td>
            </tr>
        </table>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 40%;">Department (EN)</th>
                <th style="width: 40%;">Department (AR)</th>
                <th style="width: 20%;">Staff Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row->name_en }}</td>
                    <td class="rtl">{{ $row->name_ar }}</td>
                    <td><strong>{{ $row->employee_count }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        UIMP Compliance Report — Generated dynamically by UIMP Core Platform
    </div>

</body>
</html>
