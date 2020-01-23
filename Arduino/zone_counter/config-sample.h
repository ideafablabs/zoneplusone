#ifndef CONFIG_H
#define CONFIG_H

/*
 * CONFIG DATA: PINS, WIFI PASSWORDS, ETC
 */

#define ZONE_ID 1 // Zone ID's can be found on the website.
#define READER_ID 0 // 0 for Zone, 1 for intake.

#define SSID1 "MYWIFISSID"
#define PASSWORD1 "MYWIFIPASS"

#define API_BASE "http://192.168.0.1/" // Server Location
#define API_ENDPOINT "wp-json/zoneplusone/v1/" // API Endpoint
#define LOG_FILE "actions.log"

// LED Details
#define LEDPIN 2
#define NUM_LEDS 12
#define BRIGHTNESS 100

// NFC Details
#define PN532_SCK  14
#define PN532_MOSI 13
#define PN532_SS   15
#define PN532_MISO 12
#define PN532_SS2  16

#endif // CONFIG_H