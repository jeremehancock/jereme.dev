// Most of the params can also be set in the query string
// ex: http://{yourdomain}/index.html?apikey=00000000000000000000000000000000&latitude=45.4973&longitude=-73.5707&lang=en&theme=black

var apiKey = "64c3c73a2207f72e4feee42c199bd08b"; // OpenWeatherMap api key
var latitude = "29.481137"; // search your city on google map and look at the url to get your latitude and longitude
var longitude = "-98.7945945";
var lang = "en"; // too many options.  check here https://openweathermap.org/api/one-call-api#multi
var units = "imperial"; // metric (Celsius), imperial (Fahrenheit), standard (Kelvin)
var degreeSymbol = "F"; // C or F
var rainPrecUnit = "in";
var snowPrecUnit = "in";
var windUnit = "km/h";
var forecastNbOfDays = 3; // 0 to 8
var hourlyNbOfHours = 0; // 0-49
var theme = "white"; // "blue", "black", "white"

var showScrollingAlerts = false;
var showCurrentWeather = true;
var showCurrentIcon = true;
var showCurrentSummary = true;
var showCurrentWind = false;
var showCurrentWindBearing = true;
var showCurrentHumidity = false;
var showCurrentDate = true;
var showCurrentTime = true;

var showHourlyIcon = true;
var showHourlyWind = false;
var showHourlyWindBearing = false;
var showHourlyAccumulation = true;
var showHourlyHumidity = false;
var showHourlyProbability = true;

var showForecastIcon = true;
var showForecastSummary = true;
var showForecastMinTemp = true;
var showForecastWind = false;
var showForecastWindBearing = false;
var showForecastHumidity = false;
var showForecastAccumulation = false;
var showForecastProbability = false;

var debugging = false; // will allow showing forecast for past days.  Usefull when playing with sample data

// Add your language if missing
var labelsDict =
{
    "default": {
        todayLabel: "Today",
        windLabel: "wind",
        apparentTempLabel: "feeling",
        week: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        month: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]
    },
    "fr": {
        todayLabel: "Aujourd'hui",
        windLabel: "vent",
        apparentTempLabel: "ressentie",
        week: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
        month: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
    }
};

var labels;
var url;
