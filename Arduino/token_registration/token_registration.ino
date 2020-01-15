/*
 * IFL Zone +1 Token Registration Code
 * 
 * 
 * @contributors: Jordan Layman, David Van Brink, John Szymanski, Geoff Gnau, Tané Tachyon
 * 
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
//#include <ESP8266mDNS.h>

#define count(x)   (sizeof(x) / sizeof(x[0]))

#define PIN 2
#define READER_ID 1

#define NUM_LEDS 12
#define BRIGHTNESS 50

// String API_BASE = "https://santacruz.ideafablabs.com/";
String API_BASE = "http://192.168.0.73/"; //Temporary Local
String API_ENDPOINT = "wp-json/zoneplusone/v1/";

// NFC Details
#define PN532_SCK  14
#define PN532_MOSI 13
#define PN532_SS   15
#define PN532_MISO 12
#define PN532_SS2   16

Adafruit_PN532 nfc(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS);


Adafruit_NeoPixel strip = Adafruit_NeoPixel(NUM_LEDS, PIN, NEO_GRB + NEO_KHZ800);

WiFiClient client;
HTTPClient http;
ESP8266WiFiMulti wifiMulti;

long now,lastBlink,lastRead =0;
uint16_t ledPeriod = 300; // ms
uint16_t cardreaderPeriod = 500; // ms

//int LEDPin = LED_BUILTIN;

boolean success;
uint8_t uid[] = { 0, 0, 0, 0, 0, 0, 0 };  // Buffer to store the returned UID
uint8_t uidLength;        // Length of the UID (4 or 7 bytes depending on ISO14443A card type)
uint8_t uid2[] = { 0, 0, 0, 0, 0, 0, 0 };  // Buffer to store the returned UID
uint8_t uid2Length;        // Length of the second UID (4 or 7 bytes depending on ISO14443A card type)
boolean foundCard1, foundCard2 = false;
boolean lastStatusCard1, lastStatusCard2 = false;

uint32_t colors[] = { 0xFF0000, 0xFFFF00, 0x00FF00, 0x0000FF };
uint8_t color= 100;  // number between 1-255
uint8_t colorCase= 0;

void setupWiFi() {
	WiFi.mode(WIFI_STA);
	wifiMulti.addAP("loadingdockap", "Ldock55AP$securityKEY*");
	wifiMulti.addAP("Idea Fab Labs", "vortexrings");
	// wifiMulti.addAP("ssid_from_AP_3", "your_password_for_AP_3");

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
	// put your setup code here, to run once:
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

int step =0;

void loop() {
	
	// get time
	 now = millis();
	
	 // do LEDS
	if (now >= lastBlink + ledPeriod) {
		if (foundCard1) {
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
		foundCard1 = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, &uid[0], &uidLength,100);

		if (foundCard1 == true) {
			Serial.print("Reader 1 detected card UID: ");
			Serial.println(id(uid));
			registerToken(id(uid), READER_ID);
		}
		lastRead = now;
	}
}

void registerToken(long tokenID, int readerID) {
 
	// https://apitester.com/ is handy for testing.
	Serial.println("Registering Token...");
	
	String tokenString = String(tokenID);
	
	// String requestReg = "http://santacruz.ideafablabs.com/wp-json/mint/v1/readers/1";
	String baseURI = API_BASE+API_ENDPOINT + "reader/";
	String postParams = "token_id=" + tokenString;

	String response;
	response = apiRequestPost(baseURI, postParams); 
	
	// delay(1000); /// TODO: card reader logic should take over here

}

String apiRequestPost(String request, String params) {
		
		String response;
		Serial.println("POST REQUEST: " + request + "?" + params);	

		Serial.print("[HTTP] begin...\n");
		
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
			Serial.printf("[HTTP} Unable to connect\n");
		}

		return response;
}

void checkForCard() {

}

//   
void checkForCard2() {

}

//Show real number for uid
long id(uint8_t bins[]) {
 uint32_t c;
 c = bins[0];
 for (int i=1;i<count(bins);i++){
	 c <<= 8;
	 c |= bins[i];
 }
 return c;
}

// Input a value 0 to 255 to get a color value.
// The colours are a transition r - g - b - back to r.
uint32_t Wheel(byte WheelPos) {
	WheelPos = 255 - WheelPos;
	if(WheelPos < 85) {
		return strip.Color(255 - WheelPos * 3, 0, WheelPos * 3,0);
	}
	if(WheelPos < 170) {
		WheelPos -= 85;
		return strip.Color(0, WheelPos * 3, 255 - WheelPos * 3,0);
	}
	WheelPos -= 170;
	return strip.Color(WheelPos * 3, 255 - WheelPos * 3, 0,0);
}

uint8_t red(uint32_t c) {
	return (c >> 16);
}
uint8_t green(uint32_t c) {
	return (c >> 8);
}
uint8_t blue(uint32_t c) {
	return (c);
}