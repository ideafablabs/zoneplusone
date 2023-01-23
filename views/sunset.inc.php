<div id="clock" ></div>
<div id="sunclock" ></div>

<script type="text/javascript">
    setInterval(showTime, 6000);

    let sunsetHour = 0;
    let sunsetMinute = 0;
    let sunsetNotification = 0;

    
    function getSunsetTime() {
        let sunsetTime = new Date();
        $.get('https://api.sunrise-sunset.org/json?lat=36.974117&lng=-122.030792&date=today&formatted=0',function(data,status) {
            // console.log(data);
            sunsetTime = new Date(data["results"]["sunset"]);
            sunsetHour = sunsetTime.getHours();
            sunsetMinute = sunsetTime.getMinutes();


            // Convert from Military Time
            if (sunsetHour > 12) {
                sunsetHour -= 12;
            }
            if (sunsetHour == 0) {
                sunsetHour = 12;
            }

            // hour = 8; min = 31;

            sunsetHour = sunsetHour < 10 ? "" + hour : hour;
            sunsetMinute = sunsetMinute < 10 ? "0" + min : min;
            // sec = sec < 10 ? "0" + sec : sec;

            var sunsetclock = document.getElementById("sunclock");
            // sunsetclock.innerHTML = "Sunset at " + sunsetHour + ":" + sunsetMinute;
            sunsetclock.innerHTML =  sunsetHour + ":" + sunsetMinute;
        },'json');
        
    }
    
    function showTime() {
        let time = new Date();
        let hour = time.getHours();
        let min = time.getMinutes();
        let sec = time.getSeconds();
        am_pm = "AM";
        
        //console.log(time);
        //console.log(hour);

        // reload at midnight.
        if (hour == 0 && min == 0 && sec < 7) location.reload();

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

        // hour = 4; min = 20;
        // hour = 8; min = 8;
       	// hour = 8; min = 31;
       	// hour = 11; min = 11;

        hour = hour < 10 ? "" + hour : hour;
        min = min < 10 ? "0" + min : min;
        sec = sec < 10 ? "0" + sec : sec;

        let currentTime = hour + ":" + min + am_pm;

        var clock = document.getElementById("clock");
        clock.innerHTML = currentTime;
        
        if (hour == 4 && min == 20) {
            document.body.setAttribute('class','fourtwenty');
        } else {
            document.body.setAttribute('class','');
			document.body.classList.remove('fourtwenty');

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
            document.body.setAttribute('class','eightoheight');
        } else {
			document.body.classList.remove('eightoheight');
        }
        if (hour == 11 && min == 11) {
			//document.body.classList.add('eightthreeone');
            document.body.setAttribute('class','eleveneleven');
        } else {
			document.body.classList.remove('eleveneleven');
        }


        if (time.getHours() == sunsetHour && min < sunsetMinute) {
            document.body.setAttribute('class','sunset');
        } else {
            //document.body.setAttribute('class','');
			document.body.classList.remove('sunset');

        }

        
    }
    //getSunsetTime();
    showTime();
    
    var snd = new Audio("https://santacruz.ideafablabs.com/wp-content/plugins/zoneplusone/css/Ring07.wav"); // buffers automatically when created
    snd.play();
</script>
    <style>
        #clock {
            font-size: 180px;
            color:#EEE;
            text-align: center;
            border: 2px solid #eee;
            border-radius: 16px;
            margin-top:0.9em;
            box-shadow: 2px 2px 5px 0px black;
            text-shadow:2px 2px 5px black;
        }
        #sunclock {
            color:#eee;
        }
        h1 {display:none;}
        body {
            background:#222;
            background: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/texture-background.gif';?>') 0 ;

            /* background-image: linear-gradient(to bottom right, black, gray); */
            /* background-attachment: fixed; */
        }
        body #wrap {
            background:transparent;
        }
        body.fourtwenty {
            background: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/fourtwenty-background.jpg';?>') 0 0 ;
        }
		body.eightthreeone {
            background: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/santacruz-background.jpg';?>') 0 ;
        }
        body.eightthreeone #clock {
			color:#000;
			border-color:#000;
			text-shadow:1px 1px white;
            /* background-color:rgba(0, 0, 0, 0.3) */
            /* opacity: .4; */
        }
        body.eightoheight {
            background: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/subwoofer-background.webp';?>') 0 ;
        }
        body.eleveneleven {
            background: url('<?php echo IFLZPO_PLUGIN_URL . 'css/img/cosmos-background.jpg';?>') 0 ;
        }
        html,body {
            height:inherit;
        }
</style>

<!-- <video class=" hdrelay-default-video-element" src="https://www.santacruzharbor.org/3a3b5e7c-8078-4ea3-8ebe-9fec36d61591"></video> -->
