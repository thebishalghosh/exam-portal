    </div>
</div>
</div>
<!-- Bootstrap core JS-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Core theme JS-->
<script src="/exam/public/assets/js/admin.js"></script>

<!-- Google Chart Drawing Script -->
<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawAllCharts);

    function drawAllCharts() {
        drawSubmissionsChart();
        drawStatusChart();
    }

    function drawSubmissionsChart() {
        var data = google.visualization.arrayToDataTable([
            ['Day', 'Submissions'],
            ['-6 Days', 120],
            ['-5 Days', 150],
            ['-4 Days', 130],
            ['-3 Days', 180],
            ['-2 Days', 210],
            ['Yesterday', 250],
            ['Today', 280]
        ]);

        var options = {
            title: 'Exam Submissions Trend',
            titleTextStyle: { color: '#333', fontSize: 16, bold: false },
            hAxis: {title: 'Day',  titleTextStyle: {color: '#333'}},
            vAxis: {minValue: 0},
            chartArea: {width: '85%', height: '70%'},
            legend: { position: 'none' },
            colors: ['#4CAF50'],
            areaOpacity: 0.1,
            pointSize: 7,
            pointShape: 'circle',
            animation: {
                startup: true,
                duration: 1000,
                easing: 'out',
            },
        };

        var chart = new google.visualization.AreaChart(document.getElementById('submissions_chart'));
        chart.draw(data, options);
    }

    function drawStatusChart() {
        var data = google.visualization.arrayToDataTable([
            ['Status', 'Count'],
            ['Active', 11],
            ['Inactive', 2],
            ['Completed', 8]
        ]);

        var options = {
            title: 'Exam Status Distribution',
            pieHole: 0.4,
            colors: ['#4CAF50', '#f44336', '#9e9e9e'],
            chartArea: {width: '90%', height: '80%'},
            legend: { position: 'bottom' }
        };

        var chart = new google.visualization.PieChart(document.getElementById('exam_status_chart'));
        chart.draw(data, options);
    }

    window.addEventListener('resize', drawAllCharts);
</script>
</body>
</html>
