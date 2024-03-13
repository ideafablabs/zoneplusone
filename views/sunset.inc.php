<?php 
/*
define("IFLZPO_SUNSET_IMG_URL", IFLZPO_PLUGIN_URL . 'css/img/');

// get all of the images from the folder and select one at random
$images = glob(IFLZPO_PLUGIN_PATH . 'css/img/*.png');


$image = array_rand($images);
pr($image);
// strip everything before the last slash
$image = substr($image, strrpos($image, '/'));


// <img src="<?php echo IFLZPO_SUNSET_IMG_URL . $image; ?>" alt="" />
*/
?>



<div id="clock" ></div>
<div id="sunclock" ></div>
<img id="sunset_img" src="" />



<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script type="text/javascript">
    setInterval(showTime, 1000);

    let sunsetHour = 0;
    let sunsetMinute = 0;
    let sunsetNotification = 0;
    let sunsetclock = document.getElementById("sunclock");
    let sunset_img = document.getElementById("sunset_img")
    
    let currentTimeInMinutes = 0;
    let sunsetTimeInMinutes = 0;

    function getSunsetTime() {
        let sunsetTime = new Date();
        
        
        $.get('https://api.sunrise-sunset.org/json?lat=36.974117&lng=-122.030792&date=today&formatted=0',function(data,status) {
            console.log(data);
            sunsetTime = new Date(data["results"]["sunset"]);
            sunsetHour = sunsetTime.getHours();
            sunsetMinute = sunsetTime.getMinutes();
            sunsetTimeInMinutes = sunsetHour * 60 + sunsetMinute;


            // Convert from Military Time
            if (sunsetHour > 12) {
                sunsetHour -= 12;
            }
            if (sunsetHour == 0) {
                sunsetHour = 12;
            }

            // hour = 8; min = 31;

            sunsetHour = sunsetHour < 10 ? "" + sunsetHour : sunsetHour;
            sunsetMinute = sunsetMinute < 10 ? "0" + sunsetMinute : sunsetMinute;
            // sec = sec < 10 ? "0" + sec : sec;

            var sunsetclock = document.getElementById("sunclock");
            sunsetclock.innerHTML = "Sunset: " + sunsetHour + ":" + sunsetMinute + " PM";
            //sunsetclock.innerHTML =  sunsetHour + ":" + sunsetMinute;
        },'json');
        
    }
    
    let sunset_img_interval_id = '';

    function showTime() {
        let time = new Date();
        let hour = time.getHours();
        let min = time.getMinutes();
        let sec = time.getSeconds();
        
        am_pm = "AM";
        
        // Calculate the total minutes for the current time and sunset time
        currentTimeInMinutes = hour * 60 + min;
        

        //console.log(time);
        //console.log(hour);

        // reload at 3am. (midnight was conflicting with processes and timing out on the server.)
        if (hour == 3 && min == 0 && sec < 7) location.reload();

        // Convert from Military Time
        if (hour > 12) {
            hour -= 12;
            am_pm = "PM";
        }
        if (hour == 12) {
            am_pm = "PM";
        }
        if (hour == 0) {
            hour = 12;
            am_pm = "AM";
        }

        // hour = 12; min = 34;
        // hour = 4; min = 04;
        // hour = 4; min = 20;
        // hour = 6; min = 39;
        // hour = 8; min = 8;
       	// hour = 8; min = 31;
       	// hour = 11; min = 11;
        
		// force sunset:  
        //currentTimeInMinutes = sunsetTimeInMinutes - 4;

        hour = hour < 10 ? "" + hour : hour;
        min = min < 10 ? "0" + min : min;
        sec = sec < 10 ? "0" + sec : sec;

        if (hour == 4 && min == 21 && sec <= 9) {
            hour = 4; min = 20;sec = 60+Number(sec);
        }

        let currentTime = hour + ":" + min + "<sup>:"+ sec + "</sup>" + am_pm;

        var clock = document.getElementById("clock");
        clock.innerHTML = currentTime;
        
        if (hour == 12 && min == 34) {
			//document.body.classList.add('eightthreeone');
            document.body.setAttribute('class','onetwothreefour');
        } else {
			document.body.classList.remove('onetwothreefour');
        }

        if (hour == 4 && min == 04) {
            document.body.setAttribute('class','fourohfour');
        } else {
            //document.body.setAttribute('class','');
			document.body.classList.remove('fourohfour');
        }

        if (hour == 4 && min == 20) {
            document.body.setAttribute('class','fourtwenty');
        } else {
            //document.body.setAttribute('class','');
			document.body.classList.remove('fourtwenty');
        }
		
        if (hour == 6 && min == 39) {
			//document.body.classList.add('eightthreeone');
            document.body.setAttribute('class','sixthreenine');
        } else {
			document.body.classList.remove('sixthreenine');
        }

        if (hour == 8 && min == 31) {
			//document.body.classList.add('eightthreeone');
            document.body.setAttribute('class','eightthreeone');
        } else {
			document.body.classList.remove('eightthreeone');
        }

        if (hour == 8 && min == 08) {
			//document.body.classList.add('eightthreeone');
            document.body.setAttribute('class','eightoheight');
        } else {
			document.body.classList.remove('eightoheight');
        }
        if (hour == 9 && min == 09) {
			//document.body.classList.add('eightthreeone');
            document.body.setAttribute('class','nineohnine');
        } else {
			document.body.classList.remove('nineohnine');
        }
        if (hour == 11 && min == 11) {
			//document.body.classList.add('eightthreeone');
            document.body.setAttribute('class','eleveneleven');
        } else {
			document.body.classList.remove('eleveneleven');
        }

        // if current time is 50 minutes before or 30 minutes after sunset, show the sunset notification
        if (currentTimeInMinutes >= sunsetTimeInMinutes - 120 && currentTimeInMinutes <= sunsetTimeInMinutes + 30) {
            sunset_img.style.display = "block";
            document.body.classList.add('sunset');
            let sunset_img_src = 'https://b9.hdrelay.com/camera/6cdda368-c0b1-4eab-8168-1d21f4881db6/snapshot'
            
            // if currentTimeinSeconds is within 10 seconds of the next minute, update the image
            if (sec % 10 == 0) {
                sunset_img.src = sunset_img_src + '?t=' + new Date().getTime();
                sunset_img.setAttribute('display','block');
            }

        } else {
            sunset_img.style.display = 'none';
            document.body.classList.remove('sunset');
            
            //document.getElementById('sunset_img').setAttribute('display','none');
        }
        
        // https://b9.hdrelay.com/camera/6cdda368-c0b1-4eab-8168-1d21f4881db6/snapshot

        sunsetclock.innerHTML = "Sunset: " + sunsetHour + ":" + sunsetMinute + " PM";
    }
    getSunsetTime();
    showTime();
    
    // var snd = new Audio("https://santacruz.ideafablabs.com/wp-content/plugins/zoneplusone/css/Ring07.wav"); // buffers automatically when created
    // snd.play();
</script>
    <style>
        #clock {
            font-size: 160px;
            color:#EEE;
            text-align: center;
            border: 2px solid #eee;
            border-radius: 16px;
            margin-top:15rem;
            box-shadow: 2px 2px 5px 0px black;
            text-shadow:2px 2px 5px black;
        }
        #sunclock {
            color:#eee;
            float:right;
            font-size: 1.5em;   
        }
        #sunset_img {
            position: fixed;
            top:0;
            left:0;
            height:100%;
            border-radius: 8px;
            z-index: -3;
        }
		sup {
			vertical-align:0.2em;
			font-size: .5em;
		}
        h1 {display:none;}
        body {
            background:#222;
            background: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/texture-background.gif';?>') no-repeat center center fixed ;
            /* background-image: linear-gradient(to bottom right, black, gray); */
            background-size: cover;
        
        }
        body #wrap, .content-bg, body.content-style-unboxed .site {
            background:transparent;
        }
        body .entry-content-wrap {
            padding:0;
        }
        body .content-container {
            max-width: 960px;
        }
        body.sunset #clock {
            color: orange;
            border-color: orange;
        }
        body.fourohfour {
            background-image: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/404-background.png';?>');
        }
        body.fourohfour #clock, body.fourohfour #sunclock {
            display:none;
            font-family: 'Comic Sans MS', cursive;
            color:#000;
            text-shadow:1px 1px white;
            border:none;
            box-shadow:none;
        }
        body.fourtwenty {
            background-image: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/fourtwenty-background-3.png';?>');
        }
        body.onetwothreefour {
            background-image: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/1234-background.png';?>');          
        }

        body.sixthreenine {
            background-image: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/solfeggio-background.jpg';?>');
            
        }
        body.sixthreenine #clock {
            color:#000;
            text-shadow:1px 1px white;
            border:none;
            box-shadow:none;
        }
		body.eightthreeone {
            background-image: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/santacruz-background.jpg';?>') ;
        }
        body.eightthreeone #clock {
			color:#000;
			border-color:#000;
			text-shadow:1px 1px white;
            /* background-color:rgba(0, 0, 0, 0.3) */
            /* opacity: .4; */
        }
        body.eightoheight, body.nineohnine {
            background-image: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/subwoofer-background.webp';?>');
        }
        body.eleveneleven {
            background-image: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/cosmos-background.jpg';?>');
        }
        html,body {
            height:inherit;
        }
</style>

<!-- <video class=" hdrelay-default-video-element" src="https://www.santacruzharbor.org/3a3b5e7c-8078-4ea3-8ebe-9fec36d61591"></video> -->
