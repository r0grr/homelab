<?php
include('../common.php');
include('../settings.php');
include('conversion.php');
include('chart_nav.php');

$weatherfile = date('dmY');
$dateStr = date('d/m/Y');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <title>Gràfic Combinat Temperatura i Humitat - MeteoSallent</title>
    <link rel="stylesheet" href="weather34chartstyle.css?v=2">
    <script src="../js/jquery.js"></script>
    <script src="canvasJs.js"></script>
    <script src="moments.js"></script>
</head>
<body>
<div class="weather34darkbrowser" url="Temperatura i Humitat &bull; <?php echo $dateStr; ?>"></div>

<div style="display:flex; justify-content:center; gap:8px; margin:4px auto; max-width:820px; padding:0 8px;">
    <a href="todaytemperature.php" class="chart-nav-btn">Només Temperatura</a>
    <a href="humidity.php" class="chart-nav-btn">Només Humitat</a>
    <a href="todaytemphum.php" class="chart-nav-btn active">Temp + Humitat Combinat</a>
</div>

<div class="chart-scroll-wrapper">
    <div id="chartContainer" class="chartContainer"></div>
</div>

<?php render_chart_scroller(); ?>

<script type="text/javascript">
.ready(function () {
    var dataTemp = [];
    var dataDew = [];
    var dataHum = [];

    $.ajax({
        type: "GET",
        url: "../chartswudata/<?php echo $weatherfile;?>.txt",
        dataType: "text",
        cache: false,
        success: function(data) {
            processData(data);
        }
    });

    function processData(allText) {
        var lines = allText.split('
');
        if (lines.length > 2) {
            for (var i = 2; i < lines.length; i++) {
                if (!lines[i].trim()) continue;
                var row = lines[i].split(',');
                if (row.length > 8) {
                    var tVal = parseFloat(row[1]);
                    var dewVal = parseFloat(row[2]);
                    var hVal = parseFloat(row[8]);
                    var timeLabel = moment(row[0]).format('HH:mm');

                    if (!isNaN(tVal) && tVal > -50) {
                        dataTemp.push({ label: timeLabel, y: tVal });
                    }
                    if (!isNaN(dewVal) && dewVal > -50) {
                        dataDew.push({ label: timeLabel, y: dewVal });
                    }
                    if (!isNaN(hVal) && hVal >= 0 && hVal <= 100) {
                        dataHum.push({ label: timeLabel, y: hVal });
                    }
                }
            }
            drawChart(dataTemp, dataDew, dataHum);
        }
    }

    function drawChart(dataTemp, dataDew, dataHum) {
        var chart = new CanvasJS.Chart("chartContainer", {
            backgroundColor: "RGBA(25, 30, 36, 0.95)",
            animationEnabled: true,
            title: {
                text: "Evolució Conjunta de Temperatura, Punt de Rosada i Humitat Relativa",
                fontSize: 13,
                fontColor: "#cbd5e0",
                fontFamily: "-apple-system, BlinkMacSystemFont, Arial, sans-serif",
                padding: 10
            },
            toolTip: {
                shared: true,
                backgroundColor: "RGBA(15, 23, 42, 0.92)",
                cornerRadius: 6,
                borderThickness: 1,
                borderColor: "rgba(255,255,255,0.15)",
                fontColor: "#f8fafc",
                fontSize: 12
            },
            legend: {
                fontColor: "#e2e8f0",
                fontSize: 11.5,
                fontFamily: "-apple-system, BlinkMacSystemFont, Arial, sans-serif",
                horizontalAlign: "center",
                verticalAlign: "top"
            },
            axisX: {
                gridColor: "rgba(255, 255, 255, 0.08)",
                labelFontSize: 10.5,
                labelFontColor: "#94a3b8",
                lineColor: "rgba(255, 255, 255, 0.15)",
                tickColor: "rgba(255, 255, 255, 0.15)",
                interval: 12
            },
            axisY: {
                title: "Temperatura / Rosada (&deg;C)",
                titleFontColor: "#ff8841",
                titleFontSize: 12,
                titleFontWeight: "bold",
                labelFontColor: "#ff8841",
                labelFontSize: 11,
                gridColor: "rgba(255, 255, 255, 0.06)",
                suffix: "&deg;C",
                lineColor: "#ff8841",
                tickColor: "#ff8841"
            },
            axisY2: {
                title: "Humitat Relativa (%)",
                titleFontColor: "#01a4b4",
                titleFontSize: 12,
                titleFontWeight: "bold",
                labelFontColor: "#01a4b4",
                labelFontSize: 11,
                gridColor: "transparent",
                suffix: "%",
                lineColor: "#01a4b4",
                tickColor: "#01a4b4",
                maximum: 100,
                minimum: 0
            },
            data: [
                {
                    type: "spline",
                    name: "Temperatura",
                    showInLegend: true,
                    axisYType: "primary",
                    color: "#ff8841",
                    lineThickness: 2.5,
                    markerSize: 0,
                    yValueFormatString: "#0.0 &deg;C",
                    dataPoints: dataTemp
                },
                {
                    type: "spline",
                    name: "Punt de Rosada",
                    showInLegend: true,
                    axisYType: "primary",
                    color: "#48FB9E",
                    lineThickness: 1.5,
                    lineDashType: "dash",
                    markerSize: 0,
                    yValueFormatString: "#0.0 &deg;C",
                    dataPoints: dataDew
                },
                {
                    type: "splineArea",
                    name: "Humitat Relativa",
                    showInLegend: true,
                    axisYType: "secondary",
                    color: "rgba(1, 164, 180, 0.35)",
                    lineColor: "#01a4b4",
                    lineThickness: 2,
                    markerSize: 0,
                    yValueFormatString: "#0%",
                    dataPoints: dataHum
                }
            ]
        });
        chart.render();
    }
});
</script>
</body>
</html>
