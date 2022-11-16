<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Streetlight System</title>
    
    <style type="text/css" media="screen">
    table
    {
    border-collapse: collapse;
    border-spacing: 0px;
    }
    table, th, td
    {
    padding: 5px;
    border: 1px solid black;
    }
    </style>
    
    <link rel=¨stylesheet¨ type=¨text/css¨ href=¨Server_web.css¨>
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/d6caedf4ee.js" crossorigin="anonymous"></script>
</head>
<?php
    $db_link = mysqli_connect('localhost', 'root', 'mysqltest12345', 'db') or die(mysqli_error());
?>

<body data-spy="scroll">

    <?php
        $sql = "SELECT COUNT(*) FROM streetlights";
        $status = mysqli_query($db_link, $sql);
        $temp = mysqli_fetch_assoc($status);
        $sl_count = (int)$temp["COUNT(*)"]; // # of records in the table "streetlights"
        
        /* using $chrg_status to get all streetlights' status
        * $chrg_status[0]["SN"] to get SN(id of streetlight)
        * $chrg_status[0]["charge"] = "0"(string) means it's using its own power, "1" means the opposite situation
        * $chrg_status[0]["trgt"] represents the power source
        * $chrg_status["# of streetlight's record in the table"]["kind of data"]
        */
        $sql = "SELECT * FROM streetlights";
        $status = mysqli_query($db_link, $sql);
        $chrg_status = array();
        if(mysqli_num_rows($status) > 0){
            while($row = mysqli_fetch_assoc($status) ){
                array_push($chrg_status, array("SN" => $row["SN"], "GP" => $row["grp"], "chrg" => $row["charge"], "trgt" => $row["chg_trgt"]));  // array(key => value, key => value, ...)
            }
        }
    ?>

    <form method = "post" id = "sList">
        <select id="sLight" name = "selected">
            <option value = 0>General information</option>
            <?php
                // Make groups
                $groups = array();
                foreach ($chrg_status as $element) {
                    $groups[$element['GP']][] = $element;
                }

                $SNgroups = array();    // Array of groups of SN numbers
                foreach ($groups as $gpNum => $group){
                    echo "<optgroup label = Group$gpNum>";
                    $SNarr = array();
                    foreach ($group as $STlight){
                        echo "<option value = $STlight[SN]>Light$STlight[SN]</option>";
                        $SNarr[] = $STlight["SN"];
                    }
                    $SNgroups[] = $SNarr;
                    echo "</optgroup>";
                }
            ?>
        </select>
        <input type="submit" name="submit" id="submitBtn">
    </form>

    <?php
        if(isset($_POST["brightness"])){
            $select_op = (int)$_POST["selected"];
            $brightRemap = $_POST["brightness"] / 100;  // Remap brightness from 0~100 to 0~1
            $Mode = ($_POST['mode'] == 'Auto') ? -3 : $brightRemap;
            $sql = "UPDATE streetlights SET luminance = ".$brightRemap.", user_lumi = ".$Mode." where SN = $select_op;";
            mysqli_query($db_link, $sql);
        }

        if(isset($_POST["priority"])){
            $select_op = (int)$_POST["selected"];
            $priorRemap = $_POST['priority'] + 50;  // Remap priority from 0~100 to 50~150
            $priorMode = ($_POST['Prior_mode'] == 'Auto') ? -3 : $priorRemap;
            $sql = "UPDATE streetlights SET priority = ".$priorRemap.", user_pri = ".$priorMode." where SN = $select_op;";
            mysqli_query($db_link, $sql);
        }
        
        if(isset($_POST["nearLT"]) && isset($_POST["relativPos"])){
            $cvt = array('N' => 'S', 'S' => 'N', 'W' => 'E', 'E' => 'W'); //direction conversion
            $select_op = (int)$_POST["selected"];

            // Find and delete the old position of the near streetlight to be set (if exists)
            if($_POST['nearLT'] != "0"){
                $sql = "SELECT N, S, W, E FROM streetlights WHERE SN = $select_op;";
                $status = mysqli_query($db_link, $sql);
                $row = mysqli_fetch_assoc($status);
                if($row['N'] == $_POST['nearLT'])
                    $dir = 'N';
                elseif($row['S'] == $_POST['nearLT'])
                    $dir = 'S';
                elseif($row['W'] == $_POST['nearLT'])
                    $dir = 'W';
                elseif($row['E'] == $_POST['nearLT'])
                    $dir = 'E';
                if(isset($dir)){
                    $sql = "UPDATE streetlights SET ".$dir." = 0 where SN = $select_op;";
                    mysqli_query($db_link, $sql);
                }
            }
            else{
                $sql = "SELECT ".$_POST["relativPos"]." FROM streetlights where SN = $select_op;";
                $status = mysqli_query($db_link, $sql);
                $row = mysqli_fetch_assoc($status);
                $ini_LT = $row[$_POST["relativPos"]]; // get the SN of streetlight set to 0
            }

            // Set new position of the near streetlight
            $sql = "UPDATE streetlights SET ".$_POST["relativPos"]." = ".(int)$_POST["nearLT"]." where SN = $select_op;";
            mysqli_query($db_link, $sql);

            //set new information of corresponding streetlight
            $dir = NULL;
            if($_POST['nearLT'] != "0"){
                $sql = "SELECT N, S, W, E FROM streetlights WHERE SN = ".$_POST["nearLT"].";";
                $status = mysqli_query($db_link, $sql);
                $row = mysqli_fetch_assoc($status);
                if($row['N'] == $select_op)
                    $dir = 'N';
                elseif($row['S'] == $select_op)
                    $dir = 'S';
                elseif($row['W'] == $select_op)
                    $dir = 'W';
                elseif($row['E'] == $select_op)
                    $dir = 'E';
                if(isset($dir)){
                    $sql = "UPDATE streetlights SET ".$dir." = 0 where SN = ".$_POST["nearLT"].";";
                    mysqli_query($db_link, $sql);
                }
                $sql = "UPDATE streetlights SET ".$cvt[$_POST["relativPos"]]." = ".$select_op." where SN = ".$_POST["nearLT"].";";
                mysqli_query($db_link, $sql);
            }
            elseif($ini_LT != 0){
                $sql = "UPDATE streetlights SET ".$cvt[$_POST["relativPos"]]." = 0 where SN = ".$ini_LT.";";
                mysqli_query($db_link, $sql);
            }
            
            $temp = "";
            $sql = "SELECT N,S,W,E from streetlights WHERE SN = $select_op;";
            $status = mysqli_query($db_link, $sql);
            $row = mysqli_fetch_assoc($status);
            $temp = $temp.strval($row['N']).strval($row['S']).strval($row['W']).strval($row['E']);
            
            $sql = "UPDATE streetlights SET user_loc = '$temp' WHERE SN = $select_op;";
            mysqli_query($db_link, $sql);
        }

        if(isset($_POST["powFlowStatus"])){
            $select_op = (int)$_POST["selected"];
            
            $sql = "SELECT charge, chg_trgt FROM streetlights WHERE SN = $select_op;";
            $status = mysqli_query($db_link, $sql);
            $row = mysqli_fetch_assoc($status);

            if (isset($_POST["powShareLT"])){
                // Set new power flow status (Consume or Supply) and new power sharing light
                $sql = "UPDATE streetlights SET charge = ".$_POST['powFlowStatus'].", chg_trgt = ".$_POST["powShareLT"].", chg_flag = 1 where SN = $select_op;";
                mysqli_query($db_link, $sql);

                $powFlowCvt = ($_POST['powFlowStatus'] == 1) ? 2 : 1;

                // Set new power flow status (Consume or Supply) and new power sharing light of corresponding streetlight
                $sql = "UPDATE streetlights SET charge = $powFlowCvt, chg_trgt = $select_op, chg_flag = 1 where SN = ".$_POST['powShareLT'];
                mysqli_query($db_link, $sql);
            }
            elseif ($row['charge'] !== 0){
                // Set new power flow status (Own battery)
                $sql = "UPDATE streetlights SET charge = 0, chg_trgt = 0, chg_flag = 1 where SN = $select_op;";
                mysqli_query($db_link, $sql);

                // Set new power flow status (Own battery) of corresponding streetlight
                $sql = "UPDATE streetlights SET charge = 0, chg_trgt = 0, chg_flag = 1 where SN = ".$row['chg_trgt'];
                mysqli_query($db_link, $sql);
            }

        }

        if (isset($_POST["selected"]) && $_POST['selected'] != 0){
            $select_op = $_POST['selected'];
            echo "<br><i class='fa-solid fa-plant-wilt'></i><h2 style = 'display: inline'> Streetlight $select_op</h2>";
            $sql = "SELECT * FROM streetlights WHERE SN = $select_op;";
            $status = mysqli_query($db_link, $sql);
            $temp = mysqli_fetch_assoc($status);
            $bat = (int)$temp["battery"];
            $chrg = (int)$temp["charge"]; //0 means using own power, 1 means consuming power, 2 means supplying power
            $bright = $temp["luminance"];
            $priority = $temp["priority"];
            $posStr = strval($temp['N']).strval($temp['S']).strval($temp['W']).strval($temp['E']);
            $env_lumi_pcnt = $temp["env_lumi"] / 10;    // Remap from 0~1000 to 0~100(%)
            if($chrg != 0){
                $trgt = (int)$temp["chg_trgt"];//trgt represents the power source
            }

            /*
                using $bat_arr to get the battery status of last 24 hours, start from [0]. i.e. using $bat_arr[7]["data"] to get the data of 6 o'clock
            */
            $temp = 0;
            $bat_arr = array();
            while($temp < 24){
                array_push($bat_arr, array("amount" => 0, "data" => 0));
                $temp++;
            }
            $sql = "SELECT * FROM battery_status WHERE SN = $select_op;";
            $status = mysqli_query($db_link, $sql);
            if(mysqli_num_rows($status) > 0){
                while($row = mysqli_fetch_assoc($status) ){
                    $temp = explode(":", $row['time'], 2);  // "??:??" => "??","??"
                    $temp = intval($temp[0]);   // o'clock
                    if($temp < 25){ //in case time format error
                        $bat_arr[$temp]["amount"]++;    // # of data in the hour
                        $bat_arr[$temp]["data"] += $row['battery']; // accumulate battery level in the same hour but different minutes
                    }
                }
                $temp = 0;
                while($temp < 24){
                    if($bat_arr[$temp]["amount"] != 0){
                        $bat_arr[$temp]["data"] = round($bat_arr[$temp]["data"] / $bat_arr[$temp]["amount"]);   // average battery level in the hour
                    }
                    $temp++;
                }
            }
    ?>
            <form action="" method="POST" id = "refreshForm">
                <input type="hidden" name="selected" value = <?php echo $select_op?> />
            </form>

            <script>
                // Refresh streetlight page each 30000ms(30s)
                setInterval(() => {
                    document.getElementById("refreshForm").submit();
                }, 30000);
            </script>

            <div id="myDiv" class="myDiv">
                <p>
                    <div class="slidecontainer">

                        <br>

                        <!-- Brightness -->
                        <i class="fa-regular fa-lightbulb"></i>
                        <label for="myRange">Brightness</label>
                        <form action="" method="POST">
                            <input type="range" min="0" max="100" id="myRange" name="brightness" value="0" />
                            <input type="text" name="slider_P" id="slider_P" size="5" disabled />
                            <input type="submit" name="mode" value="Manual" />
                            <input type="submit" name="mode" value="Auto" />
                            <input type="hidden" name="selected" value=<?php echo $select_op?> />   <!-- Repost the # of the streetlight -->
                        </form>
                        
                        <br>

                        <!-- Priority -->
                        <i class="fa-solid fa-ranking-star"></i>
                        <label for="Priority">Priority</label>
                        <form action="" method="POST">
                            <input type="range" min="0" max="100" id="Priority" name="priority" value="0" />
                            <input type="text" name="slider_PNum" id="slider_PNum" size="5" disabled />
                            <input type="submit" name="Prior_mode" value="Manual" />
                            <input type="submit" name="Prior_mode" value="Auto" />
                            <input type="hidden" name="selected" value=<?php echo $select_op?> />   <!-- Repost the # of the streetlight -->
                        </form>

                        <br>

                    </div>
                </p>
                <script>
                    // Range slider
                    var slider = document.getElementById("myRange");
                    var output = document.getElementById("slider_P");
                    var brightness;
                    
                    <?php
                    if (isset($bright)){
                    ?>
                        brightness = <?php echo $bright; ?> * 100;
                        slider.value = brightness;
                        output.value = brightness + "%";
                    <?php
                    }
                    else{
                    ?>
                        slider.value = 0;
                        output.value = "Not set";
                    <?php
                    }
                    ?>

                    slider.addEventListener('input', function() {
                        output.value = this.value + "%";
                    });

                    // Range slider2
                    var slider2 = document.getElementById("Priority");
                    var output2 = document.getElementById("slider_PNum");
                    var priNum;

                    <?php
                    if (isset($priority)){
                    ?>
                        priNum = <?php echo $priority; ?> - 50; // Remap priority from 50~150 to 0~100
                        slider2.value = priNum;
                        output2.value = priNum;
                    <?php
                    }
                    else{
                    ?>
                        slider2.value = 0;
                        output2.value = "Not set";
                    <?php
                    }
                    ?>

                    slider2.addEventListener('input', function() {
                        output2.value = this.value;
                    });
                </script>

                <!-- Ambient light illumination -->
                <i class="fa-solid fa-sun"></i>
                <label for="env_pcnt"> Ambient light illumination</label>

                <br>

                <input type="text" name="env_pcnt" id="env_pcnt" size="5" disabled />

                <script>
                    // Range slider
                    var illumiText = document.getElementById("env_pcnt");

                    <?php
                    if (isset($env_lumi_pcnt)){
                    ?>
                        illumiText.value = <?php echo $env_lumi_pcnt; ?> + "%";
                    <?php
                    }
                    else{
                    ?>
                        illumiText.value = "Not set";
                    <?php
                    }
                    ?>
                </script>

                <!-- Position -->
                <br><br>
                <i class="fa-solid fa-map-location-dot"></i>
                <label for="Position">Position</label>
                <form action="" method="POST">
                    <div id = "Position" style="display:block;">    <!-- Make two selection lists side-by-side -->
                        <select id = "sNearLight" name = "nearLT">
                            <option value = "0">0</option>
                            <?php
                                foreach ($SNgroups as $SNgrp){
                                    if (in_array($select_op, $SNgrp)){
                                        foreach ($SNgrp as $SN){
                                            if ($SN !== $select_op){
                                                echo "<option value = $SN>Light$SN</option>";
                                            }
                                        }
                                    }
                                }
                            ?>
                        </select>
                        <select id = "sPosition" name = "relativPos">
                            <option value = N>North</option>
                            <option value = E>East</option>
                            <option value = S>South</option>
                            <option value = W>West</option>
                        </select>
                        <input type="hidden" name="selected" value=<?php echo $select_op?> />   <!-- Repost the # of the streetlight -->
                        <input type="text" name="pos_Describe" id="pos_Describe" disabled />
                        <input type=submit value=Submit />
                    </div>
                </form>
                <br>

                <script>
                    // Position description
                    var posText = document.getElementById("pos_Describe");
                    var posInfo;
                    
                    <?php
                    $posArr = "NSWE";

                    if (isset($posStr)){
                    ?>
                        posInfo = '';
                    <?php
                        $strL = strlen($posArr);
                        for($i = 0; $i < $strL; $i++){
                    ?>
                            posInfo += "<?php echo "$posArr[$i]:$posStr[$i] " ?>";
                    <?php
                        }
                    }
                    else{
                    ?>
                        posInfo = "N:0 S:0 W:0 E:0";
                    <?php
                    }
                    ?>
                    posText.value = posInfo;
                </script>

                <!-- Power flow -->
                <i class="fa-solid fa-plug-circle-bolt"></i>
                <label for="PowerFlow">Power flow</label>
                <form action="" method="POST">
                    <div id = "PowerFlow" style="display:block;">    <!-- Make two selection lists side-by-side -->
                        <select id = "sPowerFlow" name = "powFlowStatus" onchange="opDisableFunc()">
                            <option value = 0>Own battery</option>
                            <option value = 1>Consume</option>    
                            <option value = 2>Supply</option>
                        </select>
                        <select id = "sPowShareLT" name = "powShareLT" disabled>
                            <?php
                                foreach ($SNgroups as $SNgrp){
                                    if (in_array($select_op, $SNgrp)){
                                        foreach ($SNgrp as $SN){
                                            if ($SN !== $select_op){
                                                echo "<option value = $SN>Light$SN</option>";
                                            }
                                        }
                                    }
                                }
                            ?>
                        </select>
                        <input type="hidden" name="selected" value=<?php echo $select_op?> />   <!-- Repost the # of the streetlight -->
                        <input type="text" name="powFlow_Describe" id="powFlow_Describe" size="40" disabled />
                        <input type=submit value=Submit />
                    </div>
                </form>
                <br>

                <script>
                    // Disable power sharing select list if user choose to use own battery
                    function opDisableFunc() {
                        if (document.getElementById("sPowerFlow").value == 0)
                            document.getElementById("sPowShareLT").disabled = true;
                        else
                            document.getElementById("sPowShareLT").disabled = false;
                    }

                    // Power flow status description $chrg $trgt
                    var powFlowText = document.getElementById("powFlow_Describe");
                    var powFlowInfo;
                    
                    <?php
                    if (isset($chrg)){
                        if ($chrg == 0){
                    ?>
                            powFlowInfo = "Using own battery";
                    <?php
                        }
                        elseif (isset($trgt)){
                            if ($chrg == 1){
                    ?>
                                powFlowInfo = "Power sharing : Consume Streetlight <?php echo $trgt ?>";
                    <?php
                            }
                            elseif ($chrg == 2){
                    ?>
                                powFlowInfo = "Power sharing : Supply Streetlight <?php echo $trgt ?>";
                    <?php
                            }
                        }
                        else{
                    ?>
                            posInfo = "Not set";
                    <?php
                        }
                    }
                    else{
                    ?>
                        posInfo = "Not set";
                    <?php
                    }
                    ?>
                    powFlowText.value = powFlowInfo;
                </script>

                <div class="battery-outer"> 
                    <div class="battery-level"></div>
                </div>
                <script>
                    // Battery
                    const batteryLevel = document.querySelector('.battery-level');
                    var level;

                    level = <?php echo $bat; ?>;
                    const batStatus = level + "%";
                    batteryLevel.style.width = batStatus;
                    batteryLevel.innerHTML = batStatus;
                </script>

                <br>
            
                <canvas id="myChart" style="width:100%;max-width:700px"></canvas>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            
                <script>
                    // Chart
                    <?php
                        $pow_data = array_column($bat_arr, 'data');
                    ?>

                    const labels = [
                        '0:00', '1:00', '2:00', '3:00', '4:00', '5:00', '6:00', '7:00', '8:00', '9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
                    ];
            
                    var data = {
                        labels: labels,
                        datasets: [
                            {
                                label: '電量',
                                backgroundColor: 'rgb(64, 70, 166)',
                                borderColor: 'rgb(64, 70, 166)',
                                data: <?php echo json_encode($pow_data); ?>
                            }
                        ]
                    };
            
                    var config = {
                        type: 'line',
                        data: data,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                position: 'top',
                                },
                                title: {
                                display: true,
                                text: '電力資訊'
                                }
                            }
                        },
                    };
                    var myChart = new Chart(
                        document.getElementById('myChart'),
                        config
                    );
                </script>
            </div>
    <?php
        }
        else
        {
    ?>
            <h1>Smart Streetlight Control System</h1>
            <figure>
                <img src="pics/smart_solar_lights_PR.jpg"
                width="400"
                height="341">
            </figure>

            <h2>General information</h2>
            <table>
                <tbody>
                    <tr>
                        <th>Streetlight</th>
                        <th>Power flow status</th>
                    </tr>
                    <?php
                        for($i = 0; $i < $sl_count; $i++){
                            echo "<tr><td>" . "Light ".$chrg_status[$i]['SN'] . "</td>";
                            if(!$chrg_status[$i]["chrg"])
                                echo "<td>" . "Own battery" . "</td></tr>";
                            elseif ($chrg_status[$i]["chrg"] == 1) 
                                echo "<td>" . "Power sharing : Consume Streetlight ". $chrg_status[$i]['trgt'] . "</td></tr>";
                            else
                                echo "<td>" . "Power sharing : Supply Streetlight ". $chrg_status[$i]['trgt'] . "</td></tr>";
                        }
                    ?>
                </tbody>
            </table>

            <form action="" method="POST" id = "MainPgRefresh">
                <input type="hidden" name="selected" value = 0 />
            </form>

            <script>
                // Refresh main page each 30000ms(30s)
                setInterval(() => {
                    document.getElementById("MainPgRefresh").submit();
                }, 30000);
            </script>
    <?php
        }
    ?>

</body>

</html>