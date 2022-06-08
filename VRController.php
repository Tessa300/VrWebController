<?php
const relHomeLinkProj = "/00_SubProjects/vr/";
?>
<html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>

<?php
if(!isset($_POST['SessionID'])){
    game_start();
}else {
    require_once("./Database.php");
    $res = Database::db_setControllerDevice($_POST['SessionID']);
    if ($res == false)
        game_start(Database::getLastError());
    else
        game_running($_POST['SessionID']);
}
    ?>


<?php function game_start($error = ""){?>
    <h1>Willkommen in deiner VR Steuerung</H1>
    <?php if($error != "") echo "<p>$error</p>"?>
    <form  method="post" action="<?=relHomeLinkProj?>VRController.php">
        <label>Session ID eingeben</label>
        <input type="number" name="SessionID" min=100000 max=999999 />
        <button type="submit">Starten</button>
    </form>
<?php }?>

<?php function game_running($sessionID) {?>
	<h1>Steuerung gestartet</H1>
    <p>SessionID: <?=$sessionID?></p>
<div>
<a id="btn_start" href="#" role="button">Start</a>
<p style="margin-top:1rem;">Num. of datapoints: <span class="badge badge-warning" id="num-observed-events">0</span></p>


<h4 style="margin-top:0.75rem;">Orientation</h4>
<ul>
  <li>X-axis (&beta;): <span id="Orientation_x">0</span><span>&deg;</span></li>
  <li>Y-axis (&gamma;): <span id="Orientation_y">0</span><span>&deg;</span></li>
  <li>Z-axis (&alpha;): <span id="Orientation_z">0</span><span>&deg;</span></li>
</ul>

<h4>Accelerometer</h4>
<ul>
  <li>X-axis: <span id="Accelerometer_x">0</span><span> m/s<sup>2</sup></span></li>
  <li>Y-axis: <span id="Accelerometer_y">0</span><span> m/s<sup>2</sup></span></li>
  <li>Z-axis: <span id="Accelerometer_z">0</span><span> m/s<sup>2</sup></span></li>
  <li>Data Interval: <span id="Accelerometer_i">0</span><span> ms</span></li>
</ul>

<h4>Accelerometer including gravity</h4>

<ul>
  <li>X-axis: <span id="Accelerometer_gx">0</span><span> m/s<sup>2</sup></span></li>
  <li>Y-axis: <span id="Accelerometer_gy">0</span><span> m/s<sup>2</sup></span></li>
  <li>Z-axis: <span id="Accelerometer_gz">0</span><span> m/s<sup>2</sup></span></li>
</ul>

<h4>Gyroscope</h4>
<ul>
  <li>X-axis: <span id="Gyroscope_x">0</span><span>&deg;/s</span></li>
  <li>Y-axis: <span id="Gyroscope_y">0</span><span>&deg;/s</span></li>
  <li>Z-axis: <span id="Gyroscope_z">0</span><span>&deg;/s</span></li>
</ul>

    <p id="ajaxResponse"></p>

</div>



<script>

function sendToServer(formData){
    incrementEventCount();
    if(parseInt(document.getElementById("num-observed-events").innerHTML) % 10 != 0)
        return;
    $.ajax({
        url : "<?=relHomeLinkProj?>API.php?key=VR_uniTrier&SessionID=<?=$sessionID?>&set=ControllerData",
        type: "POST",
        data : formData,
        success: function(data, textStatus, jqXHR)
        {
            document.getElementById("ajaxResponse").innerHTML = '';
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            console.error(jqXHR.responseText);
            document.getElementById("ajaxResponse").innerHTML = jqXHR.responseText;
        }
    });
}

function handleOrientation(event) {
  updateFieldIfNotNull('Orientation_z', event.alpha);
  updateFieldIfNotNull('Orientation_x', event.beta);
  updateFieldIfNotNull('Orientation_y', event.gamma);

  /* Update database */
  sendToServer({Orientation_z:event.alpha,Orientation_x:event.beta, Orientation_y:event.gamma});
}

function incrementEventCount(){
  let counterElement = document.getElementById("num-observed-events")
  let eventCount = parseInt(counterElement.innerHTML)
  counterElement.innerHTML = eventCount + 1;
}

function updateFieldIfNotNull(fieldName, value, precision=10){
  if (value != null)
    document.getElementById(fieldName).innerHTML = value.toFixed(precision);
}

function handleMotion(event) {
  updateFieldIfNotNull('Accelerometer_gx', event.accelerationIncludingGravity.x);
  updateFieldIfNotNull('Accelerometer_gy', event.accelerationIncludingGravity.y);
  updateFieldIfNotNull('Accelerometer_gz', event.accelerationIncludingGravity.z);

  updateFieldIfNotNull('Accelerometer_x', event.acceleration.x);
  updateFieldIfNotNull('Accelerometer_y', event.acceleration.y);
  updateFieldIfNotNull('Accelerometer_z', event.acceleration.z);

  updateFieldIfNotNull('Accelerometer_i', event.interval, 2);

  updateFieldIfNotNull('Gyroscope_z', event.rotationRate.alpha);
  updateFieldIfNotNull('Gyroscope_x', event.rotationRate.beta);
  updateFieldIfNotNull('Gyroscope_y', event.rotationRate.gamma);
}

let is_running = false;
let demo_button = document.getElementById("btn_start");
demo_button.onclick = function(e) {
  e.preventDefault();
  
  // Request permission for iOS 13+ devices
  if (
    DeviceMotionEvent &&
    typeof DeviceMotionEvent.requestPermission === "function"
  ) {
    DeviceMotionEvent.requestPermission();
  }
  
  if (is_running){
    window.removeEventListener("devicemotion", handleMotion);
    window.removeEventListener("deviceorientation", handleOrientation);
    demo_button.innerHTML = "Start";
    demo_button.classList.add('btn-success');
    demo_button.classList.remove('btn-danger');
    is_running = false;
  }else{
    window.addEventListener("devicemotion", handleMotion);
    window.addEventListener("deviceorientation", handleOrientation);
    document.getElementById("btn_start").innerHTML = "Stop";
    demo_button.classList.remove('btn-success');
    demo_button.classList.add('btn-danger');
    is_running = true;
  }
};

/*
Light and proximity are not supported anymore by mainstream browsers.
window.addEventListener('devicelight', function(e) {
   document.getElementById("DeviceLight").innerHTML="AmbientLight current Value: "+e.value+" Max: "+e.max+" Min: "+e.min;
});

window.addEventListener('lightlevel', function(e) {
   document.getElementById("Lightlevel").innerHTML="Light level: "+e.value;
});

window.addEventListener('deviceproximity', function(e) {
   document.getElementById("DeviceProximity").innerHTML="DeviceProximity current Value: "+e.value+" Max: "+e.max+" Min: "+e.min;
});

window.addEventListener('userproximity', function(event) {
   document.getElementById("UserProximity").innerHTML="UserProximity: "+event.near;
});
*/

</script>


<?php } ?>

</body>
</html>