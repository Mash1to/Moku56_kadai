document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("calendar");
  const tooltip = document.getElementById("tooltip");

  const calendar = new FullCalendar.Calendar(calendarEl, {
    plugins: [
      FullCalendar.DayGridPlugin,
      FullCalendar.TimeGridPlugin,
      FullCalendar.InteractionPlugin,
    ],
    initialView: "timeGridWeek",
    locale: "ja",
    nowIndicator: true,
    slotMinTime: "08:00:00",
    slotMaxTime: "20:00:00",
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay",
    },
    events: "/fetch_events.php",

    dateClick: function (info) {
      const title = prompt("予約のタイトルを入力してください：");
      if (title) {
        const start = info.dateStr;
        const end = new Date(
          new Date(start).getTime() + 60 * 60 * 1000
        ).toISOString();
        fetch("/create_event.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ title, start, end }),
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.success) {
              alert(data.message);
              calendar.refetchEvents();
            } else {
              alert(data.error || "エラーが発生しました。");
            }
          });
      }
    },

    eventMouseEnter: function (info) {
      const start = new Date(info.event.start).toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      });
      const end = new Date(info.event.end).toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      });
      tooltip.innerHTML = `<strong>${info.event.title}</strong><br>${start} - ${end}`;
      tooltip.style.left = info.jsEvent.pageX + 10 + "px";
      tooltip.style.top = info.jsEvent.pageY + 10 + "px";
      tooltip.classList.add("show");
    },
    eventMouseLeave: function () {
      tooltip.classList.remove("show");
    },
    eventMouseMove: function (info) {
      tooltip.style.left = info.jsEvent.pageX + 10 + "px";
      tooltip.style.top = info.jsEvent.pageY + 10 + "px";
    },
  });

  calendar.render();
});
