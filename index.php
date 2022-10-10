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
</head>
<?php
    $db_link = mysqli_connect('localhost', 'root', 'mysqltest12345', 'db') or die(mysqli_error());
    //echo "<p>test</p>";
?>

<body data-spy="scroll">

    <?php
        $sql = "SELECT COUNT(*) FROM streetlights";
        $status = mysqli_query($db_link, $sql);
        $temp = mysqli_fetch_assoc($status);
        $sl_count = (int)$temp["COUNT(*)"]; // # of records in the table "streetlights"
        //var_dump($count);
        /* using $chrg_status to get all streetlights' status
        * $chrg_status[0]["SN"] to get SN(id of streetlight)
        * $chrg_status[0]["charge"] = "0"(string) means it's using its own power, "1" means the opposite situation
        * $chrg_status[0]["trgt"] represents the power source
        * $chrg_status["# of streetlight's record in the table"]["kind of data"]
        */
        $sql = "SELECT * FROM streetlights";
        $status = mysqli_query($db_link, $sql);
        $chrg_status = array(); //first element empty?
        if(mysqli_num_rows($status) > 0){
            while($row = mysqli_fetch_assoc($status) ){
                //$t = array(($temp+2) => array("chrg" => $row["charge"], "trgt" => $row["chg_trgt"]));
                //echo "{$row['id']}, {$row['priority']}"."\n";
                //$chrg_status[$row["SN"]] = array("SN" => $row["SN"], "GP" => $row["grp"], "chrg" => $row["charge"], "trgt" => $row["chg_trgt"]);
                array_push($chrg_status, array("SN" => $row["SN"], "GP" => $row["grp"], "chrg" => $row["charge"], "trgt" => $row["chg_trgt"]));  // array(key => value, key => value, ...)
            }
        }
    ?>

    <form method="post">
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

                // for($i = 1; $i <= $sl_count; $i++){
                //     echo "<option value = $i>Light$i</option>";
                // }
            ?>
            <!--
            <option>Light1</option>
            <option>Light2</option>
            <option>Light3</option>
            -->
            <?php
                //include 'ajax_check.php';
                //var_dump($select_op);
            ?>
        </select>
        <input type="submit" name="submit" id="submitBtn">
    </form>

    <h1>Smart Streetlight Control System</h1>
    <figure>
        <img src="pics/smart_solar_lights_PR.jpg"
        width="400"
        height="341">
    </figure>

    <?php
        if(isset($_POST["brightness"])){
            //echo "Brightness in PHP: ".$_POST["brightness"];    // pass slider value from js to php
            //echo "<br>Select_op: ".$_POST["selected"];
            $select_op = (int)$_POST["selected"]; //this line would be sufficient, the twos above is not necessary
            $sql = "UPDATE streetlights SET luminance = ".$_POST['brightness']." where SN = $select_op;";
            mysqli_query($db_link, $sql);
        }
        // $sliderValue = $_COOKIE['slider_val'];
        // echo "<p>slider value: $sliderValue</p>";

        if(isset($_POST["priority"])){
            //echo "Priority in PHP: ".$_POST["priority"];    // pass slider2 value from js to php
            //echo "<br>Select_op: ".$_POST["selected"];
            $select_op = (int)$_POST["selected"]; //this line would be sufficient, the twos above is not necessary
            $sql = "UPDATE streetlights SET priority = ".$_POST['priority']." where SN = $select_op;";
            mysqli_query($db_link, $sql);
        }
        
        if(isset($_POST["nearLT"]) && isset($_POST["relativPos"])){
            //echo "Set position: ".$_POST["relativPos"] ." of streetlight" .$_POST["nearLT"];    // pass position value from js to php
            //echo "<br>Select_op: ".$_POST["selected"];
            $select_op = (int)$_POST["selected"]; //this line would be sufficient, the twos above is not necessary
            $sql = "UPDATE streetlights SET ".$_POST["relativPos"]." = ".$_POST["nearLT"]." where SN = $select_op;";
            mysqli_query($db_link, $sql);
        }

        if (isset($_POST["selected"]) && $_POST['selected'] != 0){
            $select_op = $_POST['selected'];
            // echo "<p>$select_op</p>"; 
            $sql = "SELECT * FROM streetlights WHERE SN = $select_op;";
            $status = mysqli_query($db_link, $sql);
            $temp = mysqli_fetch_assoc($status);
            $bat = (int)$temp["battery"];
            $chrg = (int)$temp["charge"]; //0 means using own power, 1 means using others' power
            // echo "<p>$bat</p>";
            if($chrg != 0){
                $trgt = (int)$temp["chg_trgt"];//trgt represents the power source
            }

            /*
                using $bat_arr to get the battery status of last 24 hours, start from [0]. i.e. using $bat_arr[7]["data"] to get the data of 6 o'clock
            */
            $temp = 0;
            $bat_arr = array();
            while($temp < 24){
                //$t = array(($temp+2) => array("amount" => 0, "data" => 0));
                array_push($bat_arr, array("amount" => 0, "data" => 0));
                $temp++;
            }
            $sql = "SELECT * FROM battery_status WHERE SN = $select_op;";
            $status = mysqli_query($db_link, $sql);
            if(mysqli_num_rows($status) > 0){
                while($row = mysqli_fetch_assoc($status) ){
                    $temp = explode(":", $row['time'], 2);  // "??:??" => "??","??"
                    $temp = intval($temp[0]);   // o'clock
                    //echo $temp."\n";
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
               // echo var_dump($bat_arr);
            }
            
            //echo "<p>$bat_arr[1]</p>";
    ?>
            <div id="myDiv" class="myDiv">
                <p>
                    <div class="slidecontainer">
                        <!-- <input type="range" min="0" max="100" value="50" class="slider" id="myRange"> -->
                        <form action="" method="POST">
                            <input type="range" min="0" max="100" id="myRange" name="brightness" />
                            <input type="text" name="slider_P" id="slider_P" disabled />
                            <input type=submit value=Submit />
                            <input type="hidden" name="selected" value=<?php echo $select_op?> />   <!-- Repost the # of the streetlight -->
                        </form>
                        <label for="myRange">Brightness</label>
                        <!-- <p id="rangeValue">50</p> -->

                        <form action="" method="POST">
                            <input type="range" min="1" max="100" id="Priority" name="priority" />
                            <input type="text" name="slider_PNum" id="slider_PNum" disabled />
                            <input type=submit value=Submit />
                            <input type="hidden" name="selected" value=<?php echo $select_op?> />   <!-- Repost the # of the streetlight -->
                        </form>
                        <label for="Priority">Priority</label>
                    </div>
                </p>
                <script>
                    // Range slider
                    var slider = document.getElementById("myRange");
                    var output = document.getElementById("slider_P");
                    var localStorageSliderNumber;
                    
                    if (window.localStorage.getItem('sliderValue'+<?php echo $select_op; ?>) != null) {
                        localStorageSliderNumber = window.localStorage.getItem('sliderValue'+<?php echo $select_op; ?>);
                    } else {
                        window.localStorage.setItem('sliderValue'+<?php echo $select_op; ?>, '50');
                        localStorageSliderNumber = 50;
                    }
                    slider.value = localStorageSliderNumber;
                    output.value = localStorageSliderNumber + "%";
                    // document.cookie = "slider_val=" + localStorageSliderNumber;

                    slider.addEventListener('input', function() {
                        // output.innerHTML = this.value + "%";
                        output.value = this.value + "%";
                        window.localStorage.setItem('sliderValue'+<?php echo $select_op; ?>, this.value);
                        // document.cookie = "slider_val=" + this.value;
                    });

                    // Range slider2
                    var slider2 = document.getElementById("Priority");
                    var output2 = document.getElementById("slider_PNum");
                    var localStorageSliderNumber2;

                    if (window.localStorage.getItem('slider2Value'+<?php echo $select_op; ?>) != null) {
                        localStorageSliderNumber2 = window.localStorage.getItem('slider2Value'+<?php echo $select_op; ?>);
                    }
                    slider2.value = localStorageSliderNumber2;
                    output2.value = localStorageSliderNumber2;

                    slider2.addEventListener('input', function() {
                        output2.value = this.value;
                        window.localStorage.setItem('slider2Value'+<?php echo $select_op; ?>, this.value);
                    });
                </script>

                <form action="" method="POST">
                    <div id = "Position" style="display:block;">    <!-- Make two selection lists side-by-side -->
                        <select id = "sNearLight" name = "nearLT">
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
                        <input type=submit value=Submit />
                    </div>
                </form>
                <label for="Position">Position</label>

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
            <!-- <script src="selectList.js"></script> -->
    <?php
        }
        else
        {
    ?>
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
                            /*if ($i == 1)
                            {
                                echo "<td>" . "Own battery" . "</td></tr>";
                            }
                            else if ($i == 2)
                            {
                                echo "<td>" . "Power sharing : Supply" . "</td></tr>";
                            }
                            else if ($i == 3)
                            {
                                echo "<td>" . "Power sharing : Consume" . "</td></tr>";
                            }*/
                        }
                    ?>
                </tbody>
            </table>
    <?php
        }
    ?>

</body>

</html>