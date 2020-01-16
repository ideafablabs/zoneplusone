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

#define ZONE_ID 1 // Zone ID's can be found on the website.

// String API_BASE = "https://santacruz.ideafablabs.com/";
// String API_BASE = "http://192.168.0.73/"; //Temporary Local
String API_BASE = "http://10.0.4.127/"; //Temporary Local
String API_ENDPOINT = "wp-json/zoneplusone/v1/";

// LED Details
#define LEDPIN 2
#define NUM_LEDS 12
#define BRIGHTNESS 50

// NFC Details
#define PN532_SCK  14
#define PN532_MOSI 13
#define PN532_SS   15
#define PN532_MISO 12
#define PN532_SS2   16

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
	nfc.begin();
	
	uint32_t versiondata = nfc.getFirmwareVersion();
	if (! versiondata) {
		Serial.print("Didn't find PN53x board");
		while (1); // halt
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
	Serial.println("Reader ready.  Waiting for an ISO14443A card...");

	setupWiFi();

	// LED Launch
	strip.setBrightness(BRIGHTNESS);
	strip.begin();
	strip.show(); // Initialize all pixels to 'off'
}

void loop() {
	
	// get time
	 now = millis();
	
	 // do LEDS
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
 
		// Update step
		step++;

		// Update timer
		lastBlink = now;
	}
	
	// Do card read
	if (now >= lastRead + cardreaderPeriod) {
		
		static int pollCount = 0; // just for printing the poll dots.
  	if (pollCount % 20 == 0) // so the dots dont scroll right forever.
    Serial.printf("\n%4d ", pollCount);
  	pollCount++;
  	Serial.print(".");
		
		// Capture from reader
		tokenAcquired = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, &tokenID[0], &tokenIDLength,100);

		if (tokenAcquired == true) {
			Serial.print("Reader detected tokenID: ");
			Serial.println(id(tokenID));
			plusOneZone(id(tokenID), ZONE_ID);
		}
		lastRead = now;
	}
}

void plusOneZone(long tokenID, int zoneID) {
 
	Serial.println("Plus One Zoning...");
	
	String tokenString = String(tokenID);		
	String baseURI = API_BASE+API_ENDPOINT + "zones/"+zoneID;
	String postParams = "token_id=" + tokenString;
	
	String response = apiRequestPost(baseURI, postParams); 
}

String apiRequestPost(String request, String params) {
		
	String response;
	Serial.println("POST REQUEST: " + request + "?" + params);	
	// Serial.print("[HTTP] begin...\n");
	
	if (http.begin(client, request)) {  // HTTP
		Serial.print("[HTTP] POST...\n");
		
		// add headers
		http.addHeader("Content-Type", "application/x-www-form-urlencoded");
		
		// start connection and send HTTP header
		int httpCode = http.POST(params);
		
		// httpCode will be negative on error
		if (httpCode > 0) {
			// HTTP header has been send and Server response header has been handled
			Serial.printf("[HTTP] POST... code: %d\n", httpCode);

			// file found at server
			Serial.printf("Payload: ");
			if (httpCode == HTTP_CODE_OK || httpCode == 201 || httpCode == HTTP_CODE_MOVED_PERMANENTLY) {
				response = http.getString();
				Serial.println(response);
			}
		} else {
			response = printf("Error: %s", http.errorToString(httpCode).c_str());
			Serial.printf("[HTTP] POST... failed, error: %s\n", http.errorToString(httpCode).c_str());
		}
		http.end();
	
	} else {
		Serial.printf("[HTTP} Unable to connect.\n");
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