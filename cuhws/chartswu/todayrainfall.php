<?php
	include('../common.php');
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
	
	include('../settings.php');include('conversion.php');	
    echo '
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title>OUTDOOR Rainfall Weather Underground CHART</title>	
		<script src=../js/jquery.js></script>
		<script src=canvasJs.js></script>
	';
	
	$date= date('D jS Y');$weatherfile = date('dmY');	?>
    <br>	
	<script type="text/javascript">
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
				if ( rowData[9])
				//rain rate
				dataPoints2.push({label:moment(rowData[0]).format('HH:mm'),y:parseFloat(rowData[9])});
			}
		}
		requestwindCsv();}function requestwindCsv(){		
	}

	function processData2(allText) {
		var allLinesArray = allText.split('\n');
		if(allLinesArray.length>-1){
			
			for (var i = 2; i <= allLinesArray.length-1; i++) {
				var rowData = allLinesArray[i].split(',');
				if ( rowData[12])
				//rainfall
				dataPoints1.push({label:moment(rowData[0]).format('HH:mm'),y:parseFloat(rowData[12])});
				
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
			interval:12,
			minimum:0,
			intervalType : "hour",	
			},
			
		axisY:{
		title: "<?php echo $lang['Rainfall'] ;?>  (<?php echo $rainunit ;?>)",
		titleFontColor: "#ccc",
		titleFontSize: 10,
        titleWrap: false,
		margin: 10,
		lineThickness: 0.5,		
		gridThickness: 1,		
        includeZero: true,
		gridColor: "RGBA(64, 65, 66, 0.8)",
		labelFontSize: 11,
		labelFontColor:' #ccc',
		titleFontFamily: "arial",
		labelFontFamily: "arial",
		labelFormatter: function ( e ) {
        return parseFloat(e.value.toFixed(2));  
         },		 
		 
      },
	  
	  legend:{
      fontFamily: "arial",
      fontColor:"#ccc",
  
 },
		
		
		data: [
		{
			//rainf rate
			type: "splineArea",
			color:"#FF8841",
			markerSize:2,
			showInLegend:true,
			legendMarkerType: "circle",
			lineThickness: 1,
			markerType: "circle",
			name:"<?php echo $lang['Rate'] ;?>  ",
			dataPoints: dataPoints2,
			yValueFormatString: "#0.# <?php echo $rainunit ;?>",
		},
		{
			//rain fall
			type: "splineArea",
			color:"#00A4B4",
			markerSize:2,
			showInLegend:true,
			legendMarkerType: "circle",
			lineThickness: 0,
			markerType: "circle",
			name:"<?php echo $lang['Rainfall'] ;?>  ",
			dataPoints: dataPoints1,
			yValueFormatString: "#0.# <?php echo $rainunit ;?>",
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
<div class="weather34darkbrowser" url="<?php echo $stationlocation;?> <?php echo $lang['Rainfall'] ;?> (<?php echo $rainunit ;?>) <?php echo $lang['Today'];?>"></div>
<?php include_once("chart_nav.php"); render_chart_nav("rainfall", "today"); ?>
<div class="chart-scroll-wrapper" id="chartScrollWrapper">
<div id="chartContainer" class="chartContainer"></div>
</div>
<?php include_once("chart_nav.php"); render_chart_scroller(); ?>
</body>
<script src='canvasJs.js'></script>
</html>