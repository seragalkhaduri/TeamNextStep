<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UIMP - Students Report</title>
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
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-table td {
            padding: 2px 0;
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
                    <div class="header-title">University Integrated Management Platform (UIMP)</div>
                    <div class="header-subtitle">Core Platform System Student Registry</div>
                </td>
                <td style="text-align: right; border: none; font-size: 10px; color: #4b5563;">
                    <strong>Generated At:</strong> {{ $generatedAt }}
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table" style="width: 100%;">
        <tr>
            <td><strong>Report:</strong> Active Student Registry</td>
            <td style="text-align: right;"><strong>Total Records:</strong> {{ $students->count() }}</td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 15%;">ID</th>
                <th style="width: 30%;">Full Name (EN)</th>
                <th style="width: 30%;">Full Name (AR)</th>
                <th style="width: 10%;">Gender</th>
                <th style="width: 15%;">Admission Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
                <tr>
                    <td><code>{{ $student->institutional_id }}</code></td>
                    <td>{{ $student->name_en }}</td>
                    <td class="rtl">{{ $student->name_ar }}</td>
                    <td>{{ $student->gender?->value }}</td>
                    <td>{{ $student->admission_date?->toDateString() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        UIMP Core Platform Compliance Document — Confidential — Generated automatically via UIMP Report Engine
    </div>

</body>
</html>
