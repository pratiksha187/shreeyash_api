<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .challan {
            width: 100%;
            border: 1px solid #000;
            padding: 20px;
        }
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
        .details {
            margin-top: 20px;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details th, .details td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="challan">
        <div class="header">
            DELIVERY CHALLAN<br>
            Shreeyash Construction<br>
            Khopoli, Tal- Khalapur, Dist - Raigad<br>
            Contact No: 9923299301 / 9326216153
        </div>

        <div class="details">
            <table>
                <tr>
                    <th>Challan No.</th>
                    <td>{{ $challan->challan_no }}</td>
                    <th>Date</th>
                    <td>{{ $challan->challan_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Name Of Party</th>
                    <td>{{ $challan->party_name }}</td>
                    <th>Material / M/c</th>
                    <td>{{ $challan->material_machine }}</td>
                </tr>
                <tr>
                    <th>Vehicle No.</th>
                    <td>{{ $challan->vehicle_no }}</td>
                    <th>Measurement</th>
                    <td>{{ $challan->measurement }}</td>
                </tr>
                <tr>
                    <th>Location</th>
                    <td colspan="3">{{ $challan->location }}</td>
                </tr>
                <tr>
                    <th>Time</th>
                    <td colspan="3">{{ $challan->delivery_time }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div style="float: left;">
                Receiver Sign.
            </div>
            <div style="float: right;">
                Driver Sign.
            </div>
        </div>
    </div>
</body>
</html>