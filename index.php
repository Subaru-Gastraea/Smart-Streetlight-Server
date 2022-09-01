<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Streetlight System</title>
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
        $sl_count = (int)$temp["COUNT(*)"];
        //var_dump($count);
    ?>
    <p>
    <form method="post">
        <select id="sLight" name = "selected">
            <option value = "selecting">Choose streetlight</option>
            <?php
                for($i = 1; $i <= $sl_count; $i++){
                    echo "<option value = $i>Light$i</option>";
                }
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
        if (isset($_POST["selected"])){
            $select_op = $_POST['selected'];
            // echo "<p>$select_op</p>";
            $sql = "SELECT battery FROM streetlights WHERE id = $select_op;";
            $status = mysqli_query($db_link, $sql);
            $temp = mysqli_fetch_assoc($status);
            $bat = (int)$temp["battery"];
            // echo "<p>$bat</p>";
    ?>
            <div id="myDiv">
                <div class="slidecontainer">
                    <input type="range" min="0" max="100" value="50" class="slider" id="myRange">
                    <label for="myRange">Brightness</label>
                    <p id="rangeValue">50</p>
                </div>
                <script>
                    var slider = document.getElementById("myRange");
                    var output = document.getElementById("rangeValue");
                    var localStorageSliderNumber;
                    
                    // Range slider
                    if (window.localStorage.getItem('sliderValue'+<?php echo $select_op; ?>) != null) {
                        localStorageSliderNumber = window.localStorage.getItem('sliderValue'+<?php echo $select_op; ?>);
                    } else {
                        window.localStorage.setItem('sliderValue'+<?php echo $select_op; ?>, '50');
                        localStorageSliderNumber = 50;
                    }
                    slider.value = localStorageSliderNumber;
                    output.innerHTML = localStorageSliderNumber;

                    slider.addEventListener('input', function() {
                        output.innerHTML = this.value;
                        window.localStorage.setItem('sliderValue'+<?php echo $select_op; ?>, this.value);
                    });
                </script>
            
                <div class="battery-outer"> 
                    <div class="battery-level"></div>
                </div>
                <script>
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
                    const labels = [
                        '1:00', '2:00', '3:00', '4:00', '5:00', '6:00', '7:00', '8:00', '9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00', '24:00'
                    ];
            
                    var data = {
                        labels: labels,
                        datasets: [
                            {
                                label: '發電',
                                backgroundColor: 'rgb(255, 99, 132)',
                                borderColor: 'rgb(255, 99, 132)',
                                data: [20, 10, 5, 2, 26, 30, 42, 50, 17, 5, 2, 20, 30, 17, 30, 10, 54, 26, 20, 34, 45, 44, 36, 78]
                            },
                            {
                                label: '用電',
                                backgroundColor: 'rgb(64, 70, 166)',
                                borderColor: 'rgb(64, 70, 166)',
                                data: [8, 15, 33, 21, 54, 46, 47, 50, 26, 45, 21, 13, 53, 43, 16, 19, 51, 23, 47, 36, 58, 67, 49, 23]
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
                                text: '發電及用電'
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
            <script src="selectList.js"></script>
    <?php
        }
    ?>

</body>

</html>