<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UIMP - Student Enrollment Report</title>
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
                    <div class="header-title">Student Enrollment Statistics</div>
                    <div class="header-subtitle">UIMP Enrollment Summary Report</div>
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
                <th style="width: 30%;">Faculty (EN)</th>
                <th style="width: 30%;">Faculty (AR)</th>
                <th style="width: 25%;">Program (EN)</th>
                <th style="width: 15%;">Active Students</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $faculty)
                @foreach($faculty['programs'] as $prog)
                    <tr>
                        <td>{{ $faculty['facultyNameEn'] }}</td>
                        <td class="rtl">{{ $faculty['facultyNameAr'] }}</td>
                        <td>{{ $prog['programNameEn'] }}</td>
                        <td><strong>{{ $prog['studentCount'] }}</strong></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        UIMP Compliance Report — Generated dynamically by UIMP Core Platform
    </div>

</body>
</html>
