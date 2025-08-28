<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>予約アプリ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: 'Ubuntu', sans-serif;
      background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
      margin: 0;
      padding: 0;
    }

    .header {
      text-align: center;
      padding: 2rem 1rem 1rem;
    }

    .title {
      font-size: 2rem;
      font-weight: 700;
      color: #333;
      margin-bottom: 0.5rem;
    }

    .calendar-container {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      padding: 1rem;
      max-width: 1000px;
      margin: auto;
      margin-bottom: 3rem;
    }

    #tooltip {
      position: absolute;
      background: rgba(50, 50, 93, 0.9);
      color: #fff;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 0.9rem;
      pointer-events: none;
      opacity: 0;
      transform: translateY(10px);
      transition: all 0.3s ease;
      z-index: 1000;
    }

    #tooltip.show {
      opacity: 1;
      transform: translateY(0);
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="title">📅 予約アプリ</div>
    <p>カレンダーで予約を確認できます</p>
  </div>
  <div class="calendar-container">
    <div id='calendar'></div>
  </div>
  <div id="tooltip"></div>

  <script src="assets/fullcalendar/index.global.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const calendarEl = document.getElementById('calendar');
      const tooltip = document.getElementById('tooltip');

      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'ja',
        nowIndicator: true,
        slotMinTime: '08:00:00',
        slotMaxTime: '20:00:00',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [
          {
            title: '10:00 会議',
            start: new Date().toISOString().split('T')[0] + 'T10:00:00',
            end: new Date().toISOString().split('T')[0] + 'T11:00:00',
            color: '#6c63ff'
          }
        ],
        eventMouseEnter: function(info) {
          const start = new Date(info.event.start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
          const end = new Date(info.event.end).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
          tooltip.innerHTML = `<strong>${info.event.title}</strong><br>${start} - ${end}`;
          tooltip.style.left = info.jsEvent.pageX + 10 + 'px';
          tooltip.style.top = info.jsEvent.pageY + 10 + 'px';
          tooltip.classList.add('show');
        },
        eventMouseLeave: function() {
          tooltip.classList.remove('show');
        },
        eventMouseMove: function(info) {
          tooltip.style.left = info.jsEvent.pageX + 10 + 'px';
          tooltip.style.top = info.jsEvent.pageY + 10 + 'px';
        }
      });

      calendar.render();
    });
  </script>
</body>
</html>
