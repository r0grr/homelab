<?php
include('../common.php');
include('../settings.php');
include('conversion.php');
include_once('chart_nav.php');

$date = date('D jS Y');
$dateStr = date('d/m/Y');
$weatherfile = date('dmY');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <title><?php echo $stationlocation;?> - Radiació Solar i Índex UV</title>
    <link rel="stylesheet" href="weather34chartstyle.css?ver=<?php echo filemtime(__DIR__ . "/weather34chartstyle.css"); ?>">
    <script src="../js/jquery.js"></script>
    <script src="canvasJs.js"></script>
</head>
<body>
<div class="weather34darkbrowser" url="<?php echo $stationlocation;?> &bull; Radiació Solar (Watts) i Índex UV &bull; <?php echo $dateStr;?>"></div>

<div class="chart-scroll-wrapper" id="chartScrollWrapper">
    <div id="chartContainer" class="chartContainer"></div>
</div>

<?php render_chart_scroller(); ?>

<script type="text/javascript">
$(document).ready(function () {
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
        var allLinesArray = allText.split('\n');
        if (allLinesArray.length > 2) {
            for (var i = 2; i < allLinesArray.length; i++) {
                var line = allLinesArray[i].trim();
                if (!line) continue;
                var rowData = line.split(',');
                if (rowData.length > 13) {
                    var timeLabel = moment(rowData[0]).format('HH:mm');
                    var sVal = parseFloat(rowData[13]);

                    if (!isNaN(sVal) && sVal >= 0) {
                        dataSolar.push({ label: timeLabel, y: sVal });
                    }

                    if (rowData.length > 16) {
                        var uvVal = parseFloat(rowData[16]);
                        if (!isNaN(uvVal) && uvVal >= 0) {
                            dataUv.push({ label: timeLabel, y: uvVal });
                        }
                    }

                    if (rowData.length > 17) {
                        var maxVal = parseFloat(rowData[17]);
                        if (!isNaN(maxVal) && maxVal >= 0) {
                            dataSolarMax.push({ label: timeLabel, y: maxVal });
                        }
                    }
                }
            }
            drawChart(dataSolar, dataUv, dataSolarMax);
        }
    }

    function drawChart(dataSolar, dataUv, dataSolarMax) {
        var chart = new CanvasJS.Chart("chartContainer", {
            backgroundColor: "RGBA(37, 41, 45, 0.9)",
            animationEnabled: true,
            title: {
                text: "Radiació Solar (Watts/m²) i Índex UV",
                fontSize: 12,
                fontColor: "#ccc",
                fontFamily: "arial"
            },
            toolTip: {
                fontStyle: "normal",
                cornerRadius: 4,
                backgroundColor: "RGBA(37, 41, 45, 0.9)",
                shared: true
            },
            axisX: {
                gridColor: "RGBA(64, 65, 66, 0.8)",
                labelFontSize: 10,
                labelFontColor: "#ccc",
                lineThickness: 0.5,
                gridThickness: 1,
                titleFontFamily: "arial",
                labelFontFamily: "arial",
                interval: 12,
                minimum: 0,
                intervalType: "hour"
            },
            axisY: {
                title: "Radiació Solar (Watts • W/m²)",
                titleFontColor: "#facc15",
                titleFontSize: 11,
                labelFontColor: "#facc15",
                labelFontSize: 11,
                gridColor: "RGBA(64, 65, 66, 0.8)",
                minimum: 0,
                suffix: " W/m²"
            },
            axisY2: {
                title: "Índex UV",
                titleFontColor: "#c084fc",
                titleFontSize: 11,
                labelFontColor: "#c084fc",
                labelFontSize: 11,
                gridColor: "transparent",
                minimum: 0,
                maximum: 16,
                suffix: " UV"
            },
            legend: {
                fontFamily: "arial",
                fontColor: "#ccc"
            },
            data: [
                {
                    type: "splineArea",
                    color: "rgba(250, 204, 21, 0.35)",
                    lineColor: "#facc15",
                    markerSize: 2,
                    showInLegend: true,
                    legendMarkerType: "circle",
                    lineThickness: 2.2,
                    name: "Radiació Solar (W/m²)",
                    axisYType: "primary",
                    dataPoints: dataSolar,
                    yValueFormatString: "#0.# W/m²"
                },
                {
                    type: "spline",
                    color: "#94a3b8",
                    lineDashType: "dot",
                    markerSize: 0,
                    showInLegend: true,
                    legendMarkerType: "circle",
                    lineThickness: 1.5,
                    name: "Màxim Teòric (Watts)",
                    axisYType: "primary",
                    dataPoints: dataSolarMax,
                    yValueFormatString: "#0.# W/m²"
                },
                {
                    type: "spline",
                    color: "#c084fc",
                    markerSize: 2,
                    showInLegend: true,
                    legendMarkerType: "circle",
                    lineThickness: 2.5,
                    name: "Índex UV",
                    axisYType: "secondary",
                    dataPoints: dataUv,
                    yValueFormatString: "#0.0 UV"
                }
            ]
        });
        chart.render();
    }
});
</script>
</body>
</html>