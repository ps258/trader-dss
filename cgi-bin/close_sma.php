<?php
include("../html/trader-functions.php");
require_once("../ChartDirector/lib/FinanceChart.php");
global $db_hostname, $db_database, $db_user, $db_password;

redirect_login_pf();
$username = $_SESSION['username'];
$uid = $_SESSION['uid'];

// make sure they're logged in"
if (isset($username))
{
    $c = new XYChart(640, 480);
    $portfolio = new portfolio($_SESSION['pfid']);
    $pf_id = $portfolio->getID();
    $pf_name = $portfolio->getName();
    $pf_working_date = $portfolio->getWorkingDate();
    $pf_exch = $portfolio->getExch()->getID();
    $symb = $_GET['symb'];
    $symb_name = get_symb_name($symb, $pf_exch);
    $endDate = chartTime2(strtotime($pf_working_date));
    if (isset($_SESSION['chart_period']))
    {
        $chart_period = $_SESSION['chart_period'];
    }
    else
    {
        $chart_period = 180;
    }
    $durationInDays = (int)($chart_period);
    $startDate = $endDate - ($durationInDays*24*60*60);
    $first_date = $c->formatValue($startDate, "{value|yyyy-mm-dd}");
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select a.date as date, close_ma_10, close_ma_20, close_ma_30, close_ma_50, close_ma_100, close_ma_200, open, high, low, close  from quotes a, moving_averages b where a.date = b.date and a.symb = b.symb and a.exch = b.exch and a.date >= '$first_date' and a.date <= '$pf_working_date' and a.symb = '$symb' and a.exch = '$pf_exch' order by a.date limit $chart_period;";
    foreach ($pdo->query($query) as $row)
    {
        $open[] = $row['open'];
        $close[] = $row['close'];
        $high[] = $row['high'];
        $low[] = $row['low'];
        $close_ma_10[] = $row['close_ma_10'];
        $close_ma_20[] = $row['close_ma_20'];
        $close_ma_30[] = $row['close_ma_30'];
        $close_ma_50[] = $row['close_ma_50'];
        $close_ma_100[] = $row['close_ma_100'];
        $close_ma_200[] = $row['close_ma_200'];
        $dates[] = chartTime2(strtotime($row['date']));
    }
    // Set the plotarea at (50, 30) and of size 240 x 140 pixels. Use white (0xffffff) 
    // background. 
    $plotAreaObj = $c->setPlotArea(50, 30, 410, 400);
    $plotAreaObj->setBackground(0xffffff);
    // Add a legend box at (50, 185) (below of plot area) using horizontal layout. Use 8 
    // pts Arial font with Transparent background. 
    $legendObj = $c->addLegend(50, 445, false, "", 8);
    $legendObj->setBackground(Transparent);
    // Add a title box to the chart using 8 pts Arial Bold font, with yellow (0xffff40)
    // background and a black border (0x0)
    $textBoxObj = $c->addTitle("$symb.$pf_exch: $symb_name, Simple Moving Averages from $first_date to $pf_working_date", "arialbd.ttf", 12);
    // Set the y axis label format to US$nnnn 
    $c->yAxis->setLabelFormat("£{value}");
    // Set the labels on the x axis. 
    $c->xAxis->setLabels2($dates);
    $c->xAxis->setLabelStep(7, 1);
    $m_yearFormat = "{value|yyyy}";
    $m_firstMonthFormat = "<*font=bold*>{value|mmm yy}";
    $m_otherMonthFormat = "{value|mmm}";
    $m_firstDayFormat = "<*font=bold*>{value|d mmm}";
    $m_otherDayFormat = "{value|d}";
    $m_firstHourFormat = "<*font=bold*>{value|d mmm\nh:nna}";
    $m_otherHourFormat = "{value|h:nna}";
    $m_timeLabelSpacing = 50;
    $c->xAxis->setMultiFormat(StartOfDayFilter(), $m_firstDayFormat, StartOfDayFilter(1, 0.5), $m_otherDayFormat, 1);
    $layer = $c->addLineLayer2();
    $layer->addDataSet($close_ma_10, 0xff0000, "10 day");
    $layer->addDataSet($close_ma_20, 0x00ff00, "20 day");
    $layer->addDataSet($close_ma_30, 0x0000ff, "30 day");
    $layer->addDataSet($close_ma_50, 0x008800, "50 day");
    $layer->addDataSet($close_ma_100, 0x003300, "100 day");
    $layer->addDataSet($close_ma_200, 0xff00ff, "200 day");
    $layer = $c->addHLOCLayer3($high, $low, $open, $close, 0x008000, 0xff0000);
    // Output the chart 
    header("Content-type: image/png");
    print($c->makeChart2(PNG));
}
else
{
    return;
}
?> 
