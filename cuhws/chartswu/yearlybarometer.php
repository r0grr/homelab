<?php
	
	####################################################################################################
	#	WUDATACHARTS by BRIAN UNDERDOWN 2016                                                           #
	#	CREATED FOR HOMEWEATHERSTATION TEMPLATE at http://weather34.com/homeweatherstation/index.html  # 
	# 	                                                                                               #
	# 	built on CanvasJs  	                                                                           #
	#   canvasJs.js is protected by CREATIVE COMMONS LICENCE BY-NC 3.0  	                           #
	# 	free for non commercial use and credit must be left in tact . 	                               #
	# 	                                                                                               #
	# 	Weather Data is based on your PWS upload quality collected at Weather Underground 	           #
	# 	                                                                                               #
	# 	Second General Release: 4th October 2016  	                                                   #
	# 	                                                                                               #
	#   http://www.weather34.com 	                                                                   #
	####################################################################################################
	
	include('../settings.php');include('conversion.php');include('../common.php');	
    echo '
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title>OUTDOOR YEAR Barometer DATABASE CHART</title>	
		<script src=../js/jquery.js></script>
		<script src=canvasJs.js></script>
	';
	
	$selectedYear = (isset($_GET['year']) && is_numeric($_GET['year']) && $_GET['year'] >= 2006 && $_GET['year'] <= date('Y')) ? intval($_GET['year']) : intval(date('Y'));
	$date = date('D jS Y');
	$weatherfile = $selectedYear;?>
    <br>
    	<script type="text/javascript">
		// year barometer
        $(document).ready(function () {  
	var dataPoints1 = [];
	var dataPoints2 = [];
	$.ajax({
			type: "GET",
			url: "../chartswudata/<?php echo $weatherfile;?>.txt",
			dataType: "text",
			cache:false,
			success: function(data) {processData1(data),processData2(data);}
		});
	
	function processData1(allText) {
		var allLinesArray = allText.split('\n');
		if(allLinesArray.length>0){
			
			for (var i = 2; i <= allLinesArray.length-1; i++) {
				var rowData = allLinesArray[i].split(',');
				if ( rowData.length >1)
					dataPoints1.push({label:moment(rowData[0],'YYYY-MM-DD').format("MMM Do"),y:parseFloat(rowData[10]*<?php echo $pressureconv ;?>)});
			}
		}
		requestTempCsv();}function requestTempCsv(){}

	function processData2(allText) {
		var allLinesArray = allText.split('\n');
		if(allLinesArray.length>0){
			
			for (var i = 2; i <= allLinesArray.length-1; i++) {
				var rowData = allLinesArray[i].split(',');
				if ( rowData.length >1)
					dataPoints2.push({label:moment(rowData[0],'YYYY-MM-DD').format("MMM Do"),y:parseFloat(rowData[11]*<?php echo $pressureconv ;?>)});
				
			}
			drawChart(dataPoints1 , dataPoints2 );
		}
	}

	
	function drawChart( dataPoints1 , dataPoints2 ) {
		var chart = new CanvasJS.Chart("chartContainer", {
		 backgroundColor: "RGBA(37, 41, 45, 0.9)",
		 animationEnabled: true,
		 
		title: {
            text: "",
			fontSize: 12,
			fontColor:' #ccc',
			fontFamily: "arial",
        },
		toolTip:{
			   fontStyle: "normal",
			   cornerRadius: 4,
			   backgroundColor: "RGBA(37, 41, 45, 0.9)",
		 
			   
			   toolTipContent: " x: {x} y: {y} <br/> name: {name}, label:{label}",
			   shared: true, 
 },
		axisX: {
			gridColor: "RGBA(64, 65, 66, 0.8)",
		    labelFontSize: 10,
			labelFontColor:' #ccc',
			lineThickness: 0.5,
			gridThickness: 1,	
			titleFontFamily: "arial",	
			labelFontFamily: "arial",
			interval:31,	
			},
			
		axisY:{
		title: "Barometer (<?php echo $pressureunit ;?>) Recorded",
		titleFontColor: "#ccc",
		titleFontSize: 10,
        titleWrap: false,
		margin: 10,
		lineThickness: 0.5,		
		gridThickness: 1,		
        includeZero: false,
		gridColor: "RGBA(64, 65, 66, 0.8)",
		labelFontSize: 11,
		labelFontColor:' #ccc',
		titleFontFamily: "arial",
		labelFontFamily: "arial",
		labelFormatter: function ( e ) {
        return e.value .toFixed(2) + " <?php echo $pressureunit ;?> " ;  
         },		 
		 
      },
	  
	  legend:{
      fontFamily: "arial",
      fontColor:"#ccc",
  
 },
		
		
		data: [
		{
			//type: "spline",
			type: "spline",
			color:"#3182ce",
			markerSize:2,
			showInLegend:true,
			legendMarkerType: "circle",
			lineThickness: 2,
			markerType: "circle",
			name:"Max",
			dataPoints: dataPoints1,
			yValueFormatString: "##.## <?php echo $pressureunit ;?>",
		},
		{
			type: "spline",
			color:"#00A4B4",
			markerSize:2,
			showInLegend:true,
			legendMarkerType: "circle",
			lineThickness: 2,
			markerType: "circle",
			name:"Low",
			dataPoints: dataPoints2,
			yValueFormatString: "##.## <?php echo $pressureunit ;?>",
		}

		]
		});

		chart.render();
	}
});

    </script>
  
<link rel="stylesheet" href="weather34chartstyle.css?ver=3.0">
</head>
<body>
<div class="weather34darkbrowser" url="<?php echo $stationlocation;?> <?php echo $lang['Barometer'] ;?> (<?php echo $pressureunit ;?>) <?php echo $selectedYear ;?>"></div>
<?php include_once("chart_nav.php"); render_chart_nav("barometer", "yearly", $selectedYear); ?>
<div class="chart-scroll-wrapper" id="chartScrollWrapper">
<div id="chartContainer" class="chartContainer"></div>
</div>
<?php include_once("chart_nav.php"); render_chart_scroller(); ?>
</body>
<script src='canvasJs.js'></script>
</html>