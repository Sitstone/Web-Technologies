<!DOCTYPE html>
<html>
<body>
	<script src="http://code.highcharts.com/highcharts.js"></script>
	<style>
		table{
			border-collapse: collapse;
		}
		table,td {
		    border: 1px solid black;
		    border-color: rgb(213,213,213);
		}
		form{
			text-align: center;
			width: 450px;
			border: 1px solid;
			background-color: #F3F3F3;
			margin-top: 10px;
			margin: 0px auto;
		}
		#errorTable{
			margin: 0px auto;

		}
		.StockTable{
			width: 80%;
			margin: 0px auto;
			margin-top: 10px;
		}
		.StockTable td:nth-of-type(1){
			background-color: rgb(245,245,245);
			text-align: left;
			font-weight: bold;

		}
		.StockTable td:nth-of-type(2){
			background-color: rgb(251,251,251);
			text-align: center;
		}
		#newsTable{
			width: 80%;
			text-align: left;
			margin: 0px auto;
			visibility: hidden;
			margin-top: 10px;
			background-color: rgb(251,251,251);
		}
		#newsbutton{
			background-color: white;
			padding: 0;
			border: none;
			color: rgb(188,188,188);
			margin: 0px auto;
			margin-top: 10px;
			display: block;
		}
		#container{
			border: 1px solid;
			border-color: rgb(213,213,213);
			width: 80%;
			margin: 0px auto;
			margin-top: 10px;
		}
	</style>
	<form method="GET" action="" id="stockForm">
		<p style="font-weight: bold; font-size: 30px; font-style: italic;">Stock Search</p>
		<span style="background-color: black;"></span>
		<p>
			<label for = "stock_symbol">Enter Stock Ticker Symbol:*</label>
			<input type="text" name="stock_symbol" id="searchbox" value="">
		</p>
		<span style="margin-left: 200px">
			<input type="submit" name="search" value="Search">
			<input type="reset" name="clear" value="Clear" onclick="clearPage()">
		</span>
		<p style="margin-left: -200px; font-style:italic">* - Mandatory fields.</p>
	</form>
	<script language="JavaScript">
		var json_indicators;
		var price = [], vol = [], date = [];
		var symbol = "<?php echo $_GET['stock_symbol']; ?>";

		function loadPrice(){
			chartForPrice(symbol, price.reverse(), vol.reverse(), date.reverse());
		}

		function runCharts(Indicator, data){
			if(Indicator == "Price"){
				//php get json data
				json_indicators = Object.values(data);
				//deal with price and volume
				for(var i = 0; i < 140; i++){
					price[i] = parseFloat(json_indicators[i]['4. close']);
					vol[i] = parseFloat(json_indicators[i]['5. volume']);
				}
				var keys = Object.keys(data);
				for(var i = 0; i < 140; i++){
					var day = new Date(keys[i]);
					date[i] = (day.getMonth() + 1) + "/" + (day.getDate());
				}
				chartForPrice(symbol, price, vol, date);
			}else{
				httpReques(Indicator);
				extractData(Indicator);
			} 
		}
		//http request
		function httpReques(Indicator){
			var url = "https://www.alphavantage.co/query?function=" + Indicator + "&symbol=" + symbol + "&interval=daily&time_period=10&series_type=close&apikey=C062QAALPWP8NLTP";

			if(window.XMLHttpRequest){
				xmlhttp = new XMLHttpRequest();
			}else{
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			}

			xmlhttp.open("GET", url, false);
			xmlhttp.send();
			var xmlDoc = xmlhttp.responseText;
			json_indicators = JSON.parse(xmlDoc);
		}

		function extractData(Indicator){
			var fullName = json_indicators['Meta Data']['2: Indicator'];
			var weelydata = json_indicators['Technical Analysis: ' + Indicator];
			var keys = Object.keys(weelydata);
			var date = [];
			weelydata = Object.values(weelydata);
			var data = [];

			//BBANDS has three records per week
			var bba1 = [];var bba2 = []; var bba3 = [];
			var slowK = []; var slowD = [];
			//get the well-formated date
			for(var i = 0; i < 140; i++){
				var day = new Date(keys[i]);
				date[i] = (day.getMonth() + 1) + "/" + (day.getDate());
			}

			
			//extract the data only 6 months, approximatley 140 data
			
			if(Indicator == "STOCH"){
				for(var i = 0; i < 140; i++){
					slowK[i] = parseFloat(weelydata[i]['SlowK']);
					slowD[i] = parseFloat(weelydata[i]['SlowD']);
				}
				chartForSTO(symbol, slowK, slowD, date, fullName);
				
			}else if(Indicator == "BBANDS" || Indicator == 'MACD'){
				//bbands is different, it has three data blocks
				for(var i = 0; i < 140; i++){
					bba1[i] = parseFloat(weelydata[i][Indicator == 'MACD' ? 'MACD':'Real Middle Band']);
					bba2[i] = parseFloat(weelydata[i][Indicator == 'MACD' ? 'MACD_Hist':'Real Upper Band']);
					bba3[i] = parseFloat(weelydata[i][Indicator == 'MACD' ? 'MACD_Signal':'Real Lower Band']);
				}
				chartForBBA(symbol, bba1, bba2, bba3, date, fullName, Indicator);
			}else{
				for(var i = 0; i < 140; i++){
					data[i] = parseFloat(weelydata[i][Indicator]);
				}
				generateChart(symbol, data, Indicator, date, fullName);
			}
		}

		function generateChart(symbol, data, Indicator, date, fullName){
			var myChart = Highcharts.chart('container', {
		        chart: {
		        	zoomType: 'x',
		        },
		        title: {
		            text: fullName
		        },
		        subtitle:{
		        	style:{
		        		color:'blue'
		        	},
		        	text: '<a href="https://www.alphavantage.co/">Source Alpha Vantage</a>'
		        },
		        legend: {
			        layout: 'vertical',
			        align: 'right',
			        verticalAlign: 'middle'
			    },
			    plotOptions: {
			    	series: {
			    		marker: {
			    			symbol: 'square',
			    			radius: 3
			    		},
			    		linewidth: 1
			    	}
			    },
		        xAxis: {
		        	tickInterval: 5,
		        	reversed: true,
		        	categories: date
		        },
		        yAxis: {
		        	reversed: true,
		            title: {
		                text: Indicator
		            }
		        },
		        series: [{
		            name: symbol,
		            data: data
		        }]
		    });
		}

		function chartForSTO(symbol, slowK, slowD, date, fullName){
			var myChart = Highcharts.chart('container', {
		        chart: {
		        	zoomType: 'x',
		        },
		        title: {
		            text: fullName
		        },
		        subtitle:{
		        	style:{
		        		color:'blue'
		        	},
		        	text: '<a href="https://www.alphavantage.co/">Source Alpha Vantage</a>'
		        },
		        legend: {
			        layout: 'vertical',
			        align: 'right',
			        verticalAlign: 'middle'
			    },
			    plotOptions: {
			    	series: {
			    		marker: {
			    			symbol: 'square',
			    			radius: 3
			    		},
			    		linewidth: 1
			    	}
			    },
		        xAxis: {
		        	tickInterval: 5,
		        	categories: date.reverse()
		        },
		        yAxis: {
		            title: {
		                text: 'STOCH'
		            }
		        },
		        series: [{
		            name: symbol + " SlowK",
		            data: slowK.reverse()
		        },{
		        	name: symbol + " SlowD",
		        	data: slowD.reverse()
		        }]
		    });
		}

		function chartForBBA(symbol, bba1, bba2, bba3, date, fullName, Indicator){
			var myChart = Highcharts.chart('container', {
		        chart: {
		        	zoomType: 'x',
		            type: 'line'
		        },
		        title: {
		            text: fullName
		        },
		        subtitle:{
		        	style:{
		        		color:'blue'
		        	},
		        	text: '<a href="https://www.alphavantage.co/">Source Alpha Vantage</a>'
		        },
		        legend: {
			        layout: 'vertical',
			        align: 'right',
			        verticalAlign: 'middle'
			    },
			    plotOptions: {
			    	series: {
			    		marker: {
			    			symbol: 'square',
			    			radius: 3
			    		},
			    		linewidth: 1
			    	}
			    },
		        xAxis: {
		        	tickInterval: 5,
		        	categories: date.reverse()
		        },
		        yAxis: {
		            title: {
		                text: symbol
		            }
		        },
		        series: [{
		            name: symbol + (Indicator == "MACD"? " MACD":" Real Middle Band"),
		            data: bba1.reverse()
		        },{
		        	name: symbol + (Indicator == "MACD"? " MACD_Hist":" Real Upper Band"),
		        	data: bba2.reverse()
		        },{
		        	name: symbol + (Indicator == "MACD"? " MACD_Signal":" Real Lower Band"),
		        	data: bba3.reverse()
		        }]
		    });
		}

		function chartForPrice(symbol, price, volume, date){		
			var myChart = Highcharts.chart('container', {
		        chart: {
		        	type: 'line',
		        	zoomType: 'xy'
		        },
		        title: {
		            text: 'Stock Price  (' + date[0]+ '/2017)'
		        },
		        subtitle:{
		        	style:{
		        		color:'blue'
		        	},
		        	text: '<a href="https://www.alphavantage.co/">Source Alpha Vantage</a>'
		        },
		        legend: {
			        layout: 'vertical',
			        align: 'right',
			        verticalAlign: 'middle'
			    },
			     plotOptions: {
			    	series: {
			    		marker: {
			    			radius: 1
			    		}
			    	}
			    },
		        xAxis: {
		        	tickInterval: 5,
		        	categories: date.reverse()
		        },
		        yAxis: [{
		        	tickInterval: 5,
		        	title: {
		                text: symbol
		            },
		            min: Math.min(...price) - 5
		        },
		        {
		        	title:{
		        		text: 'Volume'
		        	},
		        	labels:{
		        		format:'{value}M'
		        	},
		        	max: Math.max(...volume)/Math.pow(10,6) * 5,
		        	opposite: true
		        }],
		        series: [{
		        	type: 'area',
		            name: symbol,
		            color: 'rgb(255,139,139)',
		            data: price.reverse()
		        },
		        {	
		        	type: 'column',
		        	name: symbol + ' Volume',
		        	yAxis: 1,
		        	color: 'rgb(255,255,255)',
		        	data: volume.reverse().map(function(x){return x / Math.pow(10,6)})
		        }]
		    });
		}

		function clearPage(){
			document.getElementById("serverPart").remove();
			document.getElementById("searchbox").value = "";
		}
	</script>

	<?php if(isset($_GET["search"])): ?>
	<?php
			if($_GET["stock_symbol"] == ''){
				echo "<script>alert('Please enter a symbol')</script>";
				echo $_GET["stock_symbol"];
				return;
			}
			echo "<script>document.getElementById('searchbox').value ='".$_GET["stock_symbol"]."'</script>";
			echo "<div id='serverPart'>";
			$symbol = $_GET["stock_symbol"];
			$url = 'http://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=' .$symbol. '&outputsize=full&apikey=C062QAALPWP8NLTP';
			//get the time series daily
			$json_obj = json_decode(file_get_contents($url), true);
			if(array_keys($json_obj)[0] == "Error Message"){
				echo "<table class='StockTable'><tr><td><b>Error</b></td>";
				echo "<td>Error: NO recored has been found, please enter a valid symbol";
				return;
			}else{
				//daily transections
				$time_series = $json_obj['Time Series (Daily)'];
				$first = current($time_series);
				$second = next($time_series);
				reset($time_series);

				$close = $first['4. close'];
				$open = $first['1. open'];
				$previousClose = $second['4. close'];
				$change = $close - $previousClose;
				$volume = $first['5. volume'];
				$dayRange = $first['2. high'] . "-" . $first['3. low'];
				$timeStamp = substr($json_obj['Meta Data']['3. Last Refreshed'], 0, -8);
				//create table
				echo '<table class="StockTable">';
				echo '<tr><td>Stock Ticker Symbol</td><td>' . $symbol . '</td></tr>';
				echo '<tr><td>Close</td><td>' . $close . '</td></tr>';
				echo '<tr><td>Open</td><td>' . $open . '</td></tr>';
				echo '<tr><td>Previous Close</td><td>' . $previousClose . '</td></tr>';
				echo '<tr><td>Change</td><td>';
				if($change >= 0){
					echo round($change, 2) . "<img src = 'http://cs-server.usc.edu:45678/hw/hw6/images/Green_Arrow_Up.png' width='20' height='20'";
				}else{
					echo round($change, 2) . "<img src = 'http://cs-server.usc.edu:45678/hw/hw6/images/Red_Arrow_Down.png' width='20' height='20'";
				}
				echo '</td></tr>';
				echo '<tr><td>Change Percent</td><td>';
				if($change >= 0){
					echo  round(100 * $change / ($open == 0 ? 1 : $open), 2) ."%<img src = 'http://cs-server.usc.edu:45678/hw/hw6/images/Green_Arrow_Up.png' width='20' height='20'>";
				}else{
					echo  round(100 * $change / ($open == 0 ? 1 : $open), 2) ."%<img src = 'http://cs-server.usc.edu:45678/hw/hw6/images/Red_Arrow_Down.png' width='20' height='20'>";
				}
				echo '</td></tr>';
				echo '<tr><td>Day\'s Range</td><td>' . $dayRange . '</td></tr>';
				echo '<tr><td>Volume</td><td>' . number_format($volume,0,".",",") . '</td></tr>';
				echo '<tr><td>Timestamp</td><td>' . $timeStamp . '</td></tr>';

				echo '<tr><td>Indicators</td><td>';
				echo '<a href="#" id="price" onclick="loadPrice()">Price</a>';
				echo '   ';
				echo '<a href="#" id="sma" onclick="runCharts(\'SMA\',0)">SMA</a>';
				echo '   ';
				echo '<a href="#" id="ema" onclick="runCharts(\'EMA\',0)">EMA</a>';
				echo '   ';
				echo '<a href="#" id="sto" onclick="runCharts(\'STOCH\',0)">STOCH</a>';
				echo '   ';
				echo '<a href="#" id="rsi" onclick="runCharts(\'RSI\',0)">RSI</a>';
				echo '   ';
				echo '<a href="#" id="adx" onclick="runCharts(\'ADX\',0)">ADX</a>';
				echo '   ';
				echo '<a href="#" id="cci" onclick="runCharts(\'CCI\',0)">CCI</a>';
				echo '   ';
				echo '<a href="#" id="bba" onclick="runCharts(\'BBANDS\',0)">BBANDS</a>';
				echo '   '; 
				echo '<a href="#" id="macd" onclick="runCharts(\'MACD\',0)">MACD</a></td></tr></table>';
				echo '   ';
				echo '<p id="container"><script>document.addEventListener("DOMContentLoaded", runCharts("Price"';
				echo ','.json_encode($time_series).'),false)</script></p>'; 
			}
	?>
		
	<button id="newsbutton" onclick="showNews()">
		<span id="buttontext">click to show stock news</span><br>
		<span><img id="buttonimg" src="http://cs-server.usc.edu:45678/hw/hw6/images/Gray_Arrow_Down.png" width="50px" height="20px"></span>
	</button>

	<script type="text/javascript">
		function showNews() {
    		var x = document.getElementById("newsTable");
    		if (x.style.visibility === "hidden" || x.style.visibility === "") {
        		x.style.visibility = "visible";
        		document.getElementById('buttontext').innerHTML = "click to hide stock news";
        		document.getElementById('buttonimg').src = "http://cs-server.usc.edu:45678/hw/hw6/images/Gray_Arrow_Up.png";
    		} else {
        		x.style.visibility = "hidden";
        		document.getElementById("buttontext").innerHTML = "click to show stock news";
        		document.getElementById('buttonimg').src = "http://cs-server.usc.edu:45678/hw/hw6/images/Gray_Arrow_Down.png";
    		}
		}
	</script>
	<?php 
		$news_url = 'https://seekingalpha.com/api/sa/combined/'.$symbol.'.xml';
			$xmldata = @file_get_contents($news_url);//surpass the warning
			echo '<table id = "newsTable">';
			if($xmldata == true){
				$xmldata = simplexml_load_string($xmldata);
				$counter = 0;
				
				//store the news
				foreach($xmldata->channel->item as $singleNews){
					if($counter == 5) break;
					echo "<tr><td><a href=".$singleNews->link.">".$singleNews->title."</a>      Publicated Time: ".substr($singleNews->pubDate, 0, -5)."</td></tr>";
					$counter++;
				}
			}
			echo '</table></div>';
	?>
<?php endif  ?>

</body>
</html>