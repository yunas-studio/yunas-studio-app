/*
Template Name: Minible - Admin & Dashboard Template
Author: Themesbrand
Website: https://themesbrand.com/
Contact: support@themesbrand.com
File: Monthly Expenses Chart
*/

// get colors array from the string
function getChartColorsArray(chartId) {
    if (document.getElementById(chartId) !== null) {
        var colors = document.getElementById(chartId).getAttribute("data-colors");
        if (colors) {
            colors = JSON.parse(colors);
            return colors.map(function (value) {
                var newValue = value.replace(" ", "");
                if (newValue.indexOf(",") === -1) {
                    var color = getComputedStyle(document.documentElement).getPropertyValue(newValue);
                    if (color) return color;
                    else return newValue;
                } else {
                    var val = value.split(',');
                    if (val.length == 2) {
                        var rgbaColor = getComputedStyle(document.documentElement).getPropertyValue(val[0]);
                        rgbaColor = "rgba(" + rgbaColor + "," + val[1] + ")";
                        return rgbaColor;
                    } else {
                        return newValue;
                    }
                }
            });
        }
    }
}

// Monthly Expenses Chart
document.addEventListener("DOMContentLoaded", function () {
    var monthlyExpensesData = JSON.parse(document.getElementById('monthly-expenses-data').getAttribute('data-expenses'));
    var monthlyLabels = JSON.parse(document.getElementById('monthly-expenses-data').getAttribute('data-labels'));
    
    var options = {
        chart: {
            height: 350,
            type: 'bar',
            toolbar: {
                show: false,
            }
        },
        plotOptions: {
            bar: {
                columnWidth: '45%',
                distributed: false
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: 2
        },
        series: [{
            name: 'Expenses',
            data: monthlyExpensesData
        }],
        colors: getChartColorsArray("monthly-expenses-chart"),
        xaxis: {
            categories: monthlyLabels,
            labels: {
                style: {
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Amount (Rp)',
            },
            labels: {
                formatter: function (value) {
                    return 'Rp ' + value.toLocaleString('id-ID');
                }
            }
        },
        grid: {
            borderColor: '#f1f1f1'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        }
    };

    var chart = new ApexCharts(
        document.querySelector("#monthly-expenses-chart"),
        options
    );

    chart.render();
}); 