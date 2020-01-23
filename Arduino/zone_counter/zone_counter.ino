/*
 * IFL Zone +1 Token Registration Code
 * Send the token ID from an NFC or RFID card up to an api endpoint.
 * 
 * @contributors: Jordan Layman, David Van Brink, John Szymanski, Geoff Gnau, Tané Tachyon
 */

#include <Adafruit_NeoPixel.h>
#include <Wire.h>
#include <SPI.h>
#include <Adafruit_PN532.h>

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <WiFiClient.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include "FS.h"

// Setup Config.h by duplicating config-sample.h.
#include "config.h"

Adafruit_PN532 nfc(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS);
Adafruit_NeoPixel strip = Adafruit_NeoPixel(NUM_LEDS, LEDPIN, NEO_GRB + NEO_KHZ800);

WiFiClient client;
HTTPClient http;
ESP8266WiFiMulti wifiMulti;

#define count(x) (sizeof(x) / sizeof(x[0]))

long now,lastBlink,lastRead =0;
uint16_t ledPeriod = 300; // ms
uint16_t cardreaderPeriod = 500; // ms

boolean success;
uint8_t tokenID[] = { 0, 0, 0, 0, 0, 0, 0 };  // Buffer to store the returned TokenID
uint8_t tokenIDLength;        // Length of the TokenID (4 or 7 bytes depending on ISO14443A card type)
boolean tokenAcquired = false;
boolean lastStatusCard1 = false;

uint32_t colors[] = { 0xFF0000, 0xFFFF00, 0x00FF00, 0x0000FF };
uint8_t color = 1;  // number between 1-255
uint8_t colorCase = 0;
int step = 0 ;

void setupWiFi() {
	WiFi.mode(WIFI_STA);
	
	wifiMulti.addAP("Idea Fab Labs", "vortexrings");
	wifiMulti.addAP("omino warp", "0123456789");
	wifiMulti.addAP("ssid_from_AP_3", "your_password_for_AP_3");

	while (wifiMulti.run() != WL_CONNECTED) {
		Serial.println("WiFi not connected!");
		delay(1000);
	} 
	Serial.println("");
	Serial.println("WiFi connected");
	Serial.println("IP address: ");
	Serial.println(WiFi.localIP());
	Serial.println(WiFi.SSID());
}

void setup() {

	Serial.begin(115200);
	Serial.println("Hello!");

	// LED Launch.
	strip.setBrightness(BRIGHTNESS);
	strip.begin();
	showAll(0xFF0000);

	nfc.begin();
	
	uint32_t versiondata = nfc.getFirmwareVersion();
	if (! versiondata) {
		Serial.print("Didn't find PN53x board");
		delay(1000); // wait a second and give it a go.
    	ESP.restart();
	}
	// Got ok data, print it out!
	Serial.print("Found chip PN532"); Serial.println((versiondata>>24) & 0xFF, HEX); 
	Serial.print("Firmware ver. "); Serial.print((versiondata>>16) & 0xFF, DEC); 
	Serial.print('.'); Serial.println((versiondata>>8) & 0xFF, DEC);

	// Set the max number of retry attempts to read from a card
	// This prevents us from waiting forever for a card, which is
	// the default behaviour of the PN532.
	nfc.setPassiveActivationRetries(0x01);

	// Configure board to read RFID tags
	nfc.SAMConfig();
	Serial.println("NFC ready. Waiting for an ISO14443A card...");

	showAll(0x0000FF);

	setupWiFi();

	// Start the file system.
	SPIFFS.begin();
	logAction("Booted Up");
	// readLog();	

	showAll(0x00FF00);
}

void loop() {
	
	// Get time.
	now = millis();
	
	 // Do LEDS...
	if (now >= lastBlink + ledPeriod) {
		if (tokenAcquired) {
			for(int i=0; i<NUM_LEDS; i++){
				strip.setPixelColor(i, 0xFF00FF);
			}  
		} else {
			for(int i=0; i<NUM_LEDS; i++){
				strip.setPixelColor(i,0);
			}
			strip.setPixelColor(step % NUM_LEDS, 0xFF00FF);
		}

		// Let the magic happen.
		strip.show();
 
		// Update step.
		step++;

		// Update timer.
		lastBlink = now;
	}
	
	// Do card read.
	if (now >= lastRead + cardreaderPeriod) {
		
		static int pollCount = 0; // just for printing the poll dots.
  		if (pollCount % 60 == 0) // so the dots dont scroll right forever.
			Serial.printf("\n%4d ", pollCount);
  		pollCount++;
  		Serial.print(".");
		
		// Capture from reader
		tokenAcquired = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, &tokenID[0], &tokenIDLength,100);

		if (tokenAcquired == true) {

			logAction("Reader detected tokenID: " + (String)id(tokenID));			
			
			if (READER_ID) {				
				registerToken(id(tokenID), READER_ID);
			} else {
				plusOneZone(id(tokenID), ZONE_ID);
			}
		}
		lastRead = now;
	}
}

void plusOneZone(long tokenID, int zoneID) {
 
	String tokenString = String(tokenID);		
	String baseURI = API_BASE+API_ENDPOINT + "zones/"+zoneID;
	String postParams = "token_id=" + tokenString;	
	
	String response = apiRequestPost(baseURI, postParams); 
}

void registerToken(long tokenID, int readerID) {
	
	String tokenString = String(tokenID);
	String baseURI = API_BASE+API_ENDPOINT + "reader/";
	String postParams = "token_id=" + tokenString;

	String response = apiRequestPost(baseURI, postParams); 
}

String apiRequestPost(String request, String params) {
		
	String response;
	logAction("POST REQUEST: " + request + "?" + params);
	
	if (http.begin(client, request)) {  // HTTP
				
		// Craft HTTP Header and start connection...
		http.addHeader("Content-Type", "application/x-www-form-urlencoded");
		int httpCode = http.POST(params);
		
		// httpCode will be negative on error
		if (httpCode > 0) {

			// HTTP header has been sent and Server response header has been handled
			logAction("[HTTP] POST RESPONSE... code: "+(String)httpCode);

			// file found at server
			
			if (httpCode == HTTP_CODE_OK || httpCode == 201 || httpCode == HTTP_CODE_MOVED_PERMANENTLY) {
				response = http.getString();
				// Serial.println(response);
			}

		} else {
			response = "Error: " + http.errorToString(httpCode);
			// Serial.println(response);
		}
		
		logAction(response);
		
		http.end();
	
	} else {
		logAction("[HTTP] Unable to connect.");
	}

	return response;
}

//Show real number for tokenID
long id(uint8_t bins[]) {
	uint32_t c;
	c = bins[0];
	for (int i=1;i<count(bins);i++){
		c <<= 8;
		c |= bins[i];
	}
	return c;
}
// === LED Functions ===
void showAll(uint32_t color) {
	for(int i=0; i<NUM_LEDS; i++){
	    strip.setPixelColor(i,color);
	}
	strip.show();
}

// === Log Functions ===
void readLog() {
	
	int xCnt = 0;
  
	File f = SPIFFS.open(LOG_FILE, "r");
  
	if (!f) {
		Serial.println("file open failed");
  	}  Serial.println("====== Reading from LOG_FILE =======");

	while(f.available()) {
      //Lets read line by line from the file
      String line = f.readStringUntil('\n');
      Serial.print(xCnt);
      Serial.print("  ");
      Serial.println(line);
      xCnt ++;
    }
    f.close();    
}

void flushLog() {
	File f = SPIFFS.open(LOG_FILE, "w");	
		f.printf("%s %s: ", __DATE__, __TIME__);
		f.println("Begin Log");
	f.close();
}

void logAction(String actionString) {
    
	Serial.printf("\n%s %s: ", __DATE__, __TIME__);
	Serial.println(actionString);

	char* mode = "a";

	// if not exists, create using W, other wise append with A
	if (!SPIFFS.exists(LOG_FILE)) mode = "w";

	File f = SPIFFS.open(LOG_FILE, mode);			
		f.printf("%s %s: ", __DATE__, __TIME__);
		f.println(actionString);
	f.close();
    
}
