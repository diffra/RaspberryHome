<?php
  
/*
*   +------------------------------------------------------------------------------+
*       SERVER STATUS SCRIPT                                                               
*   +------------------------------------------------------------------------------+
*/ 


/*
*   +------------------------------------------------------------------------------+
*        Configuration
*   +------------------------------------------------------------------------------+
*/
//Enter the hostname/dynamic DNS address for your server
$hostname = "home.bertelson.me";

//Temperature readouts -- enter C or F (Default: C)
$degtype = "F";

// Define service checks
$services = Array(
	Array("80", 				"Internet Connection", 					"google.com"),
	Array("80",					"HTTP (Hyper Text Transfer Protocol)", 	""),
	Array("php5-fpm",		 	"PHP5-FPM", 							""),
	Array("3306", 				"MySQL (Database server)", 				""),
	Array("445", 				"Samba (Windows Network File Share)", 	""),
	Array("21", 				"FTP", 									""),
	Array("22", 				"Internal SSH", 						""),
	Array("62484", 				"External SSH", 						"home.bertelson.me"),
	Array("9091", 				"Transmission", 						""),
	Array("5000", 				"ZNC IRC server", 						""),
	Array("8888", 				"BitTorrent Sync", 						""),
	Array("tightvnc",		 	"TightVNC (Remote Desktop)", 			""),
	Array("unbound", 			"Unbound (DNS Server)", 				""),
	Array("emulationstation", 	"EmulationStation (Gaming)", 			"")

);
// Define Header Links
$links = Array(
	Array("Transmission", 		"/transmission/gui/"),
	Array("BT Sync", 			"/btsync/gui/"),
	Array("Jinzora", 			"/jinzora/"),
	Array("TT-RSS", 			"/tt-rss"),
	Array("Owncloud", 			"http://cloud.bertelson.me"),
	Array("VNC", 				"/vnc/")
);	
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
//Start timer
$time_start = microtime_float();

//Get IP address
$_ip = gethostbyname($hostname);

//Generate Service table
$data0 = "";
foreach ($services as $service) {
	$status = "offline";
	if(is_numeric($service[0])){
		if($service[2]==""){
			$service[2] = "localhost";
		}

		$fp = @fsockopen("$service[2]", $service[0], $errno, $errstr, 1);
								
		if ($fp) {
			$status = "online";
			fclose($fp);
		}
					
		@fclose($fp);
	} else{
		exec("pgrep $service[0]", $output, $return);
						
		if ($return == 0) {
			$status = "online";
		}
	}
	
	$data0 .= "<tr><td>$service[1]</td><td class='status-$status'>$status</td></tr>"; //'#FFC6C6' '#D9FFB3'
}


//GET SERVER LOADS
$loadresult = @exec('uptime'); 
preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/",$loadresult,$avgs);


//GET SERVER UPTIME
  $uptime = explode(' up ', $loadresult);
  $uptime = explode(',', $uptime[1]);
  $uptime = $uptime[0].', '.$uptime[1];

$data1 = "<tr><td>Load Average </td><td>$avgs[1], $avgs[2], $avgs[3]</td>\n";
$data1 .= "<tr><td>Server Uptime</td><td>".$uptime."</td></tr>\n";  
  
//GET MEMORY DATA
  $used = `free -m | grep "buffers/cache" | awk '{print $3}'`;
  $totalram = chop(`free -m | grep Mem | awk '{print $2}'`);
  $usedram_percent = round($used*100/$totalram);

$data1 .= "<tr><td>Memory In Use	</td><td>$usedram_percent% (".$used."MB/".$totalram."MB)</td></tr>\n";

$rootfs = chop(`df -h | grep rootfs | awk '{ print $5}'`);
$rootfssize = chop(`df -h | grep rootfs | awk '{print $2}'`);
$rootfsused = chop(`df -h | grep rootfs | awk '{print $3}'`);
$data1 .= "<tr><td>SD Card	</td><td>$rootfs ($rootfsused/$rootfssize)</td></tr>\n";

$mediafs = chop(`df -h | grep sda1 | awk '{ print $5}'`);
$mediafssize = chop(`df -h | grep sda1 | awk '{print $2}'`);
$mediafsused = chop(`df -h | grep sda1 | awk '{print $3}'`);
$data1 .= "<tr><td>Hard Disk	</td><td>$mediafs ($mediafsused/$mediafssize)</td></tr>\n";

  
//get ps data
  $ps = (`ps aux | wc -l`)-1;

$data1 .= "<tr><td>Server processes	</td><td>$ps Processes</td></tr>\n";  
  
//Get network connection total
$numtcp = `netstat -nt | grep tcp | wc -l`;
$numudp = `netstat -nu | grep udp | wc -l`;

$data1 .= "<tr><td>Open Connections	</td><td>TCP: $numtcp\tUDP: $numudp</td></tr>\n";

//Temperature value
if ($degtype == "F") {
$degrees="&deg;F";
}
else{
$degrees="&deg;C";
}


$cputemp= `cat /sys/class/thermal/thermal_zone0/temp`/1000;
if ($degtype == "F") {
$cputemp = $cputemp*9/5+32;
}
$cputemp= round(($cputemp), 1);

$data1 .= "<tr><td>CPU Temp	</td><td>$cputemp $degrees</td></tr>\n";

$gputemp= `/opt/vc/bin/vcgencmd measure_temp | sed 's/temp=//' | sed 's/.C//'`;
if ($degtype == "F") {
$gputemp = $gputemp*9/5+32;
}
$gputemp= round(($gputemp), 1);
$data1 .= "<tr><td>GPU Temp	</td><td>$gputemp $degrees</td></tr>\n";

//Generate Links
//<li><a href="/transmission">Transmission</a></li>
foreach ($links as $link){
$linkdata .= "<li><a href='$link[1]'>$link[0]</a></li>";
}

?>
<!DOCTYPE html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="en"> <!--<![endif]-->
<head>

    <!-- Basic Page Needs
  ================================================== -->
	<meta charset="utf-8">
	<title>Raspberry Homepage</title>
	
    <!-- Mobile Specific Metas
  ================================================== -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    
    <!-- CSS
  ================================================== -->
	<link rel="stylesheet" href="css/zerogrid.css">
	<link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
	<link rel="stylesheet" href="css/flexslider.css" type="text/css" media="screen" />
	
	<!--[if lt IE 8]>
       <div style=' clear: both; text-align:center; position: relative;'>
         <a href="http://windows.microsoft.com/en-US/internet-explorer/products/ie/home?ocid=ie6_countdown_bannercode">
           <img src="http://storage.ie6countdown.com/assets/100/images/banners/warning_bar_0000_us.jpg" border="0" height="42" width="820" alt="You are using an outdated browser. For a faster, safer browsing experience, upgrade for free today." />
        </a>
      </div>
    <![endif]-->
    <!--[if lt IE 9]>
		<script src="js/html5.js"></script>
		<script src="js/css3-mediaqueries.js"></script>
	<![endif]-->
	
	<link href='./images/favicon.png' rel='icon' type='image/x-icon'/>    
</head>
<body>
<!--------------Header--------------->
<div class="wrap-header">
<header> 
	<div id="logo"><a href="#"><img src="./images/logo.png"/></a></div>
	
	<nav>
		<ul>
			<?php echo $linkdata; ?>
		</ul>
	</nav>
</header>
</div>
			
<!--------------Content--------------->
<section id="content">
	<div class="zerogrid">		
		<div class="row">
			<div id="main-content">
				<article style="margin-left: 20px;">
					<div class="heading">
						<h2>Service Status</h2>
					</div>
					<div class="content">
						<table style="width:500px;">
							<tr><th>Service</th><th>Status</th></tr>
							<?php
								echo $data0;
							?>
						</table>
					</div>
				</article>
				<article style="position: absolute; top: 0px; left: 650px;">
					<div class="heading">
						<h2>System Status</h2>
					</div>
					<div class="content">
						<table style="width:500px;">
						<tr><th />&nbsp;<th /></tr>
						<?php
							echo $data1;
						?>
						</table>
					</div>
				</article>
			</div>
		</div>
	</div>
</section>
<!--------------Footer--------------->
<div class="wrap-footer">
	<footer>
		<div class="wrapfooter">
			<p>Page generated in <?php echo round(microtime_float() - $time_start, 1); ?> seconds</p>
		</div>
	</footer>
</div>
</body></html>
