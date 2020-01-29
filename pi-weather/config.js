var apiKey = "5ad157ae89dbcf93bf7a27585be177b8"; // darksky.net api key
var latitude = "29.481137"; // Showing Montreal.  search your city on google map and look at the url to get your latitude and longitude
var longitude = "-98.7945945";
var lang = "en"; // too many options.  check here https://darksky.net/dev/docs/forecast
var units = "us"; // auto, ca, uk2, us, si
var degreeSymbol = "F"; // C or F
var rainPrecUnit = "mm";
var snowPrecUnit = "cm";
var windUnit = "km/h"
var forecastNbOfDays = 5; // 0 to 8
var hourlyNbOfHours = 0; // 0-49
var theme = "white"; // "blue", "black", "white"

var showScrollingAlerts = true;
var showCurrentWeather = true;
var showCurrentIcon = true;
var showCurrentSummary = true;
var showCurrentWind = false;
var showCurrentWindBearing = false;
var showCurrentHumidity = true;
var showCurrentDate = true;
var showCurrentTime = true;

var showHourlyIcon = true;
var showHourlyWind = false;
var showHourlyWindBearing = false;
var showHourlyAccumulation = false;
var showHourlyHumidity = false;
var showHourlyProbability = false;

var showForecastIcon = true;
var showForecastSummary = true;
var showForecastMinTemp = true;
var showForecastWind = false;
var showForecastWindBearing = false;
var showForecastHumidity = false;
var showForecastAccumulation = false;
var showForecastProbability = false;


var showDarkSkyLink = true; // set to true if you have a free darksky api key.  

var debugging = false; // will allow showing forecast for past days.  Usefull when playing with sample data

// Set for your language
// English
var todayLabel = "Today";
var windLabel = "wind";
var apparentTempLabel = "feels like";
var week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
var month = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

// French
//var todayLabel = "Aujourd'hui";
//var windLabel = "vent";
//var apparentTempLabel = "ressentie";
//var week = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
//var month = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

var url = 'https://api.darksky.net/forecast/' + apiKey + '/' + latitude + ',' + longitude + '?lang=' + lang + '&units=' + units;
