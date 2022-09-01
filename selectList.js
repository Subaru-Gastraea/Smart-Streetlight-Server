var myDiv = document.getElementById("myDiv");
var selectElem = document.getElementById("sLight");

var production1 = [20, 10, 5, 2, 26, 30, 42, 50, 17, 5, 2, 20, 30, 17, 30, 10, 54, 26, 20, 34, 45, 44, 36, 78];
var consumption1 = [8, 15, 33, 21, 54, 46, 47, 50, 26, 45, 21, 13, 53, 43, 16, 19, 51, 23, 47, 36, 58, 67, 49, 23];
var production;
var consumption;
var electricData;

selectElem.addEventListener('change', function(){
    myDiv.style.display = "none";

    // Chart
    if (window.localStorage.getItem('chart'+selectElem.selectedIndex) != null) {
        electricData = window.localStorage.getItem('chart'+selectElem.selectedIndex);
        production = electricData.split(',').slice(0,24);
        consumption = electricData.split(',').slice(24);
    } else {
        production = production1.map(x => x * selectElem.selectedIndex);
        consumption =  consumption1.map(x => x * selectElem.selectedIndex);
        electricData = [production, consumption];
        window.localStorage.setItem('chart'+selectElem.selectedIndex, electricData);
    }
    myChart.data.datasets[0].data = production;
    myChart.data.datasets[1].data = consumption;
    myChart.update();
});