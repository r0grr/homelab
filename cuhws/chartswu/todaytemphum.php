<?php
include('../common.php');
include('../settings.php');
include('conversion.php');
include_once('chart_nav.php');

$weatherfile = date('dmY');
$dateStr = date('d/m/Y');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <title><?php echo $stationlocation;?> - Evolució de Temperatura, Humitat i Rosada</title>
    <link rel="stylesheet" href="weather34chartstyle.css?ver=<?php echo filemtime(__DIR__ . "/weather34chartstyle.css"); ?>">
    <script src="../js/jquery.js"></script>
    <script src="canvasJs.js"></script>
</head>
<body>
<div class="weather34darkbrowser" url="<?php echo $stationlocation;?> &bull; Temperatura, Humitat i Punt de Rosada &bull; <?php echo $dateStr; ?>"></div>

<div style="display:flex; justify-content:center; gap:8px; margin:4px auto; max-width:820px; padding:0 8px; flex-wrap:wrap;">
    <a href="todaytemphum.php" class="chart-nav-btn active">Temperatura + Humitat + Rosada</a>
    <a href="todaytemperature.php" class="chart-nav-btn">Temperatura + Rosada (Històric)</a>
    <a href="humidity.php" class="chart-nav-btn">Només Humitat</a>
</div>

<div class="chart-scroll-wrapper" id="chartScrollWrapper">
    <div id="chartContainer" class="chartContainer"></div>
</div>

<?php render_chart_scroller(); ?>

<script type="text/javascript">
$(document).ready(function () {
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
        var allLinesArray = allText.split('\n');
        if (allLinesArray.length > 2) {
            for (var i = 2; i < allLinesArray.length; i++) {
                var line = allLinesArray[i].trim();
                if (!line) continue;
                var rowData = line.split(',');
                if (rowData.length > 8) {
                    var tVal = parseFloat(rowData[1]);
                    var dewVal = parseFloat(rowData[2]);
                    var hVal = parseFloat(rowData[8]);
                    var timeLabel = (typeof moment !== 'undefined') ? moment(rowData[0]).format('HH:mm') : (rowData[0].length >= 16 ? rowData[0].substr(11, 5) : rowData[0]);

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
                title: "Temperatura i Rosada (<?php echo $tempunit; ?>)",
                titleFontColor: "#ff9350",
                titleFontSize: 12,
                titleFontWeight: "bold",
                labelFontColor: "#ff9350",
                labelFontSize: 11,
                gridColor: "rgba(255, 255, 255, 0.06)",
                suffix: " <?php echo $tempunit; ?>",
                lineColor: "#ff9350",
                tickColor: "#ff9350"
            },
            axisY2: {
                title: "Humitat Relativa (%)",
                titleFontColor: "#00d2d3",
                titleFontSize: 12,
                titleFontWeight: "bold",
                labelFontColor: "#00d2d3",
                labelFontSize: 11,
                gridColor: "transparent",
                suffix: "%",
                lineColor: "#00d2d3",
                tickColor: "#00d2d3",
                maximum: 100,
                minimum: 0
            },
            data: [
                {
                    type: "spline",
                    name: "Temperatura",
                    showInLegend: true,
                    axisYType: "primary",
                    color: "#ff9350",
                    lineThickness: 2.5,
                    markerSize: 0,
                    yValueFormatString: "#0.0 <?php echo $tempunit; ?>",
                    dataPoints: dataTemp
                },
                {
                    type: "spline",
                    name: "Punt de Rosada",
                    showInLegend: true,
                    axisYType: "primary",
                    color: "#34d399",
                    lineThickness: 1.8,
                    lineDashType: "dash",
                    markerSize: 0,
                    yValueFormatString: "#0.0 <?php echo $tempunit; ?>",
                    dataPoints: dataDew
                },
                {
                    type: "splineArea",
                    name: "Humitat Relativa",
                    showInLegend: true,
                    axisYType: "secondary",
                    color: "rgba(0, 210, 211, 0.28)",
                    lineColor: "#00d2d3",
                    lineThickness: 2,
                    markerSize: 0,
                    yValueFormatString: "#0'%'",
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
