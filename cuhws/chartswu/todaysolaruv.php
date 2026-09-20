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
    <title>Gràfic Radiació Solar i Índex UV - MeteoSallent</title>
    <link rel="stylesheet" href="weather34chartstyle.css?ver=<?php echo filemtime(__DIR__ . "/weather34chartstyle.css"); ?>">
    <script src="../js/jquery.js"></script>
    <script src="canvasJs.js"></script>
    <script src="moments.js"></script>
</head>
<body>
<div class="weather34darkbrowser" url="Radiació Solar (W/m&sup2;) i Índex UV &bull; <?php echo $dateStr; ?>"></div>

<div style="display:flex; justify-content:center; gap:8px; margin:4px auto; max-width:820px; padding:0 8px;">
    <a href="todaysolar.php" class="chart-nav-btn">Només Radiació Solar</a>
    <a href="todaysolaruv.php" class="chart-nav-btn active">Solar + UV + Watts Combinat</a>
    <a href="../uvindexds.php" class="chart-nav-btn">Guia UV Oficial</a>
</div>

<div class="chart-scroll-wrapper">
    <div id="chartContainer" class="chartContainer"></div>
</div>

<?php render_chart_scroller(); ?>

<script type="text/javascript">
.ready(function () {
    var dataSolar = [];
    var dataUv = [];
    var dataSolarMax = [];

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
                if (row.length > 13) {
                    var sVal = parseFloat(row[13]);
                    var timeLabel = moment(row[0]).format('HH:mm');

                    if (!isNaN(sVal) && sVal >= 0) {
                        dataSolar.push({ label: timeLabel, y: sVal });
                    }

                    if (row.length > 16) {
                        var uvVal = parseFloat(row[16]);
                        if (!isNaN(uvVal) && uvVal >= 0) {
                            dataUv.push({ label: timeLabel, y: uvVal });
                        }
                    }

                    if (row.length > 17) {
                        var sMaxVal = parseFloat(row[17]);
                        if (!isNaN(sMaxVal) && sMaxVal >= 0) {
                            dataSolarMax.push({ label: timeLabel, y: sMaxVal });
                        }
                    }
                }
            }
            drawChart(dataSolar, dataUv, dataSolarMax);
        }
    }

    function drawChart(dataSolar, dataUv, dataSolarMax) {
        var chart = new CanvasJS.Chart("chartContainer", {
            backgroundColor: "RGBA(25, 30, 36, 0.95)",
            animationEnabled: true,
            title: {
                text: "Radiació Solar (Watts/m&sup2;), Màxima Teòrica i Índex Ultraviolat UV",
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
                title: "Radiació Solar (W/m&sup2;)",
                titleFontColor: "#ecb454",
                titleFontSize: 12,
                titleFontWeight: "bold",
                labelFontColor: "#ecb454",
                labelFontSize: 11,
                gridColor: "rgba(255, 255, 255, 0.06)",
                suffix: " W/m&sup2;",
                lineColor: "#ecb454",
                tickColor: "#ecb454",
                minimum: 0
            },
            axisY2: {
                title: "Índex UV",
                titleFontColor: "#ff4757",
                titleFontSize: 12,
                titleFontWeight: "bold",
                labelFontColor: "#ff4757",
                labelFontSize: 11,
                gridColor: "transparent",
                suffix: " UV",
                lineColor: "#ff4757",
                tickColor: "#ff4757",
                maximum: 16,
                minimum: 0
            },
            data: [
                {
                    type: "splineArea",
                    name: "Radiació Solar",
                    showInLegend: true,
                    axisYType: "primary",
                    color: "rgba(236, 180, 84, 0.35)",
                    lineColor: "#ecb454",
                    lineThickness: 2.2,
                    markerSize: 0,
                    yValueFormatString: "#0 W/m&sup2;",
                    dataPoints: dataSolar
                },
                {
                    type: "spline",
                    name: "Màxim Teòric",
                    showInLegend: true,
                    axisYType: "primary",
                    color: "#94a3b8",
                    lineDashType: "dot",
                    lineThickness: 1.5,
                    markerSize: 0,
                    yValueFormatString: "#0 W/m&sup2;",
                    dataPoints: dataSolarMax
                },
                {
                    type: "spline",
                    name: "Índex UV",
                    showInLegend: true,
                    axisYType: "secondary",
                    color: "#ff4757",
                    lineThickness: 2.5,
                    markerSize: 0,
                    yValueFormatString: "#0.0 UV",
                    dataPoints: dataUv
                }
            ]
        });
        chart.render();
    }
});
</script>
</body>
</html>
