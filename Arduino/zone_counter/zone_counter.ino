/*
 * IFL Zone +1 Token Registration Code
 * Send the token ID from an NFC or RFID card up to an api endpoint.
 * 
 * @contributors: Jordan Layman, David Van Brink, John Szymanski, Geoff Gnau, Tané Tachyon
 */

#include <Adafruit_NeoPixel.h>
#include <SPI.h>
#include <Adafruit_PN532.h>

#include <ESP8266WiFiMulti.h>
#include <WiFiClient.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <ESPAsyncTCP.h>
#include <asyncHTTPrequest.h>
#include "FS.h"

// Setup Config.h by duplicating config-sample.h.
#include "config.h"

Adafruit_PN532 nfc(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS);
Adafruit_NeoPixel strip = Adafruit_NeoPixel(NUM_LEDS, LEDPIN, NEO_GRB + NEO_KHZ800);

WiFiClient client;
ESP8266WiFiMulti wifiMulti;
asyncHTTPrequest async;

long now,lastBlink,lastRead =0;
uint16_t ledPeriod = 300; // ms
uint16_t cardreaderPeriod = 500; // ms

typedef uint32_t nfcid_t; // we treat the NFCs as 4 byte values throughout, for easiest.
uint8_t tokenID[] = { 0, 0, 0, 0, 0, 0, 0 };  // Buffer to store the returned TokenID
uint8_t tokenIDLength;        // Length of the TokenID (4 or 7 bytes depending on ISO14443A card type)
boolean tokenAcquired = false;
boolean lastStatusCard1 = false;

uint32_t colors[] = { 0xFF0000, 0xFFFF00, 0x00FF00, 0x0000FF };
uint8_t color = 1;  // number between 1-255
uint8_t colorCase = 0;
int step = 0 ;

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

	showAll(0x00FF00);

	// Start the file system.
	SPIFFS.begin();
	setupClient();
	
	logAction("Booted Up");
	// readLog();	
	
}

void loop() {
	
	// Get time.
	now = millis();
	
	// +-------------------------
	// | Poll the NFC
	static nfcid_t lastID = -1;
	static nfcid_t tokenID = -1;

	if (now >= lastRead + cardreaderPeriod) { // time for next poll?
  
		tokenID = pollNfc();
	 	
	 	if (tokenID != lastID) { // Detect change in card.
	 			 		
	 		if (tokenID != 0){
				logAction("Reader detected tokenID: " + (String)tokenID);

				// reader state becomes active


				if (READER_ID) {									
					registerToken(tokenID, READER_ID);
				} else {
					plusOneZone(tokenID, ZONE_ID);
				}				
			} else {				
				// reader state becomes inactive.

			}
			lastID = tokenID;
		} else {
			// increase hold timer.

		}
		lastRead = now;
	}

	// +-------------------------
	// | Do LEDs.	
	if (now >= lastBlink + ledPeriod) {
		
		if (tokenID) {
			for(int i=0; i<NUM_LEDS; i++){
				strip.setPixelColor(i, 0);
			}
			strip.setPixelColor(step % NUM_LEDS, 0x00FFFF);  
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
	
}

// https://github.com/boblemaire/asyncHTTPrequest
// https://stackoverflow.com/questions/54820798/how-to-receive-json-response-from-rest-api-using-esp8266-arduino-framework
void onClientStateChange(void * arguments, asyncHTTPrequest * aReq, int readyState) {
  
  switch (readyState) {
    case 0: // readyStateUnsent: Client created, open not yet called.
      break;

    case 1: // readyStateOpened: open() has been called, connected    	
      break;

    case 2: // readyStateHdrsRecvd: send() called, response headers available
      break;

    case 3:	// readyStateLoading: receiving, partial data available
      break;

    case 4: // readyStateDone: Request complete, all data available.

    	// Log Response.
    	logAction(aReq->responseHTTPcode()+" "+aReq->responseText());

      break;
  }
}

void plusOneZone(long tokenID, int zoneID) {
 
	String tokenString = String(tokenID);		
	String baseURI = API_BASE+API_ENDPOINT + "zones/"+zoneID;
	String params = "token_id=" + tokenString;	
	
	startAsyncRequest(baseURI,params,"POST");
}

void registerToken(long tokenID, int readerID) {
	
	String tokenString = String(tokenID);
	String baseURI = API_BASE+API_ENDPOINT + "reader/";
	String params = "token_id=" + tokenString;	

	startAsyncRequest(baseURI,params,"POST");
}

// Wifi Setup.
void setupWiFi() {
	WiFi.mode(WIFI_STA);
	
	wifiMulti.addAP(SSID1, PASSWORD1);
	wifiMulti.addAP(SSID2, PASSWORD2);

	while (wifiMulti.run() != WL_CONNECTED) {
		Serial.println("WiFi not connected!");
		delay(1000);
	} 
	
	logAction("WiFi connected to SSID: '"+WiFi.SSID()+"' @ "+WiFi.localIP().toString());
}

// Async Setup.
void setupClient() {
  async.setTimeout(5);
  async.setDebug(false);
  async.onReadyStateChange(onClientStateChange);
}

void startAsyncRequest(String request, String params, String type){
    
	logAction(type + " REQUEST: " + request + "?" + params);
    
	if(async.readyState() == 0 || async.readyState() == 4){		
		async.open(type.c_str(),request.c_str());
		if (type == "POST") async.setReqHeader("Content-Type","application/x-www-form-urlencoded");
		async.send(params);	
	}
}

// Return the 64 bit uid, with ZERO meaning nothing presently detected.
nfcid_t pollNfc()
{
	uint8_t uidBytes[8] = {0};
	uint8_t uidLength;
	nfcid_t uid = 0;

	static int pollCount = 0; // just for printing the poll dots.
	char pollChar = '.'; // dots for no read, + for active.

	// Check for card
	int foundCard = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, &uidBytes[0], &uidLength, 100);

	if (foundCard) {		
		uidLength = 4; // it's little endian, the lower four are enough, and all we can use on this itty bitty cpu. ///magic numbers
	 	
	 	// Unspool the bins right here.
	 	for (int ix = 0; ix < uidLength; ix++)
			uid = (uid << 8) | uidBytes[ix];

		pollChar = '+'; //
	}

	
	if (pollCount % 20 == 0)  // so the dots dont scroll right forever.
		Serial.printf("\n%4d ", pollCount);
	pollCount++;
	Serial.print(pollChar);
	return uid;
}

// Count Macro.
#define count(x) (sizeof(x) / sizeof(x[0]))

//Show real number for tokenID.
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
