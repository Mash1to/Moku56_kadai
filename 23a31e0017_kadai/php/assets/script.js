document.addEventListener('DOMContentLoaded', () => {
  const calendarEl = document.getElementById('calendar');
  if (!calendarEl) return;

  const tooltip = document.getElementById('tooltip');

  // ====== 便利関数 ======
  const pad = (n) => n.toString().padStart(2, '0');
  function toMysqlDatetime(d) {
    return (
      d.getFullYear() + '-' +
      pad(d.getMonth() + 1) + '-' +
      pad(d.getDate()) + ' ' +
      pad(d.getHours()) + ':' +
      pad(d.getMinutes()) + ':' +
      pad(d.getSeconds())
    );
  }

  async function fetchJson(url, options) {
    const res = await fetch(url, options);
    const text = await res.text();
    let data = null;
    try { data = JSON.parse(text); } catch (_) {}

    if (!res.ok) {
      const msg = (data && data.error) ? data.error : `HTTP ${res.status}: ${text.slice(0, 120)}`;
      throw new Error(msg);
    }
    if (!data) throw new Error(`${url} がJSONを返していません: ${text.slice(0, 120)}`);
    return data;
  }

  // ====== 予約ダイアログ ======
  const dlg = document.getElementById('reserveDialog');
  const form = document.getElementById('reserveForm');
  const btnCancel = document.getElementById('reserveCancel');

  const rStart = document.getElementById('rStart');
  const rEnd = document.getElementById('rEnd');
  const rTitle = document.getElementById('rTitle');
  const rLocation = document.getElementById('rLocation');
  const rWho = document.getElementById('rWho');
  const rDesc = document.getElementById('rDesc');

  const hasDialog =
    dlg && form && btnCancel &&
    rStart && rEnd && rTitle && rLocation && rWho && rDesc;
  const reserveError = document.getElementById('reserveError');

  function showReserveError(msg){
    if (!reserveError) return;
    reserveError.textContent = msg;
    reserveError.classList.add('show');
  }
  function clearReserveError(){
    if (!reserveError) return;
    reserveError.textContent = '';
    reserveError.classList.remove('show');
  }


  let pending = null;

  // ====== tooltip追従（eventMouseMoveは使わない） ======
  let tooltipOn = false;
  let lastMouse = { x: 0, y: 0 };
  document.addEventListener('mousemove', (e) => {
    lastMouse = { x: e.pageX, y: e.pageY };
    if (tooltipOn && tooltip) {
      tooltip.style.left = (lastMouse.x + 10) + 'px';
      tooltip.style.top  = (lastMouse.y + 10) + 'px';
    }
  });

  function openReserve(start, end) {
    pending = { start, end };

    const startLabel = start.toLocaleString('ja-JP');
    const endLabel = end.toLocaleString('ja-JP');

    if (hasDialog) {
      rStart.value = startLabel;
      rEnd.value = endLabel;
      rTitle.value = '';
      rLocation.value = '';
      rWho.value = '';
      rDesc.value = '';
      clearReserveError(); 
      dlg.showModal();
      rTitle.focus();
      return;
    }

    // dialogがない場合のフォールバック
    const title = prompt(`予約タイトル\n（${startLabel}〜${endLabel}）`);
    if (!title) { pending = null; return; }

    (async () => {
      try {
        const data = await fetchJson('create_event.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            title,
            start: toMysqlDatetime(start),
            end: toMysqlDatetime(end),
            location: '',
            who_name: '',
            description: ''
          })
        });
        if (data.success) calendar.refetchEvents();
      } catch (err) {
        alert(err.message || '予約登録に失敗しました');
      } finally {
        pending = null;
      }
    })();
  }

  // ====== FullCalendar ======
  const calendar = new FullCalendar.Calendar(calendarEl, {
    height: 'parent',
    expandRows: true,
    handleWindowResize: true,

    initialView: 'timeGridWeek',
    locale: 'ja',
    nowIndicator: true,

    selectable: true,
    selectMirror: true,

    slotDuration: '00:15:00',
    snapDuration: '00:05:00',
    slotMinTime: '08:00:00',
    slotMaxTime: '20:00:00',

    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },

    events: 'load_events.php',

    // ドラッグ選択→予約
    select: function(sel) {
      openReserve(sel.start, sel.end);
      calendar.unselect();
    },

    // クリック予約（monthは日表示へ）
    dateClick: function(info) {
      if (calendar.view.type === 'dayGridMonth') {
        calendar.changeView('timeGridDay', info.date);
        return;
      }
      const start = new Date(info.date);
      const end = new Date(start.getTime() + 60 * 60 * 1000);
      openReserve(start, end);
    },

    // クリックでキャンセル
    eventClick: function(info) {
      info.jsEvent.preventDefault();
      info.jsEvent.stopPropagation();

      const ok = confirm(`この予約をキャンセルしますか？\n\nタイトル: ${info.event.title}`);
      if (!ok) return;

      (async () => {
        try {
          const data = await fetchJson('delete_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: info.event.id })
          });
          if (data.success) {
            info.event.remove();
            calendar.refetchEvents();
          }
        } catch (err) {
          alert(err.message || 'キャンセルに失敗しました');
        }
      })();
    },

    // tooltip（enter/leave）
    eventMouseEnter: function(info) {
      if (!tooltip) return;
      tooltipOn = true;

      const p = info.event.extendedProps || {};
      const loc = p.location ? `場所: ${p.location}<br>` : '';
      const who = p.who_name ? `誰が: ${p.who_name}<br>` : '';
      const desc = p.description ? `メモ: ${String(p.description).replace(/\n/g, '<br>')}` : '';

      const s = new Date(info.event.start);
      const e = info.event.end ? new Date(info.event.end) : null;
      const st = s.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      const en = e ? e.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

      tooltip.innerHTML = `<strong>${info.event.title}</strong><br>${st}${en ? ' - ' + en : ''}<br>${loc}${who}${desc}`;
      tooltip.style.left = (lastMouse.x + 10) + 'px';
      tooltip.style.top  = (lastMouse.y + 10) + 'px';
      tooltip.classList.add('show');
    },

    eventMouseLeave: function() {
      if (!tooltip) return;
      tooltipOn = false;
      tooltip.classList.remove('show');
    },

    datesSet: function() {
      calendar.updateSize();
      setTimeout(setupCursorBadge, 0);
    }
  });

  calendar.render();
  setTimeout(() => calendar.updateSize(), 0);
  window.addEventListener('load', () => calendar.updateSize());

  // ====== 安全なカーソル時間表示（timeGrid内だけ） ======
  let cursorBadge = null;

  function setupCursorBadge() {
    const scroller = calendarEl.querySelector('.fc-timegrid-body .fc-scroller');
    if (!scroller) return;

    if (!cursorBadge) {
      cursorBadge = document.createElement('div');
      cursorBadge.className = 'fc-cursor-time';
      scroller.appendChild(cursorBadge);
    }

    const show = (e) => {
      if (!calendar.view.type.startsWith('timeGrid')) {
        cursorBadge.classList.remove('show');
        return;
      }

      const lane = e.target.closest('td.fc-timegrid-slot-lane');
      const time = e.target.closest('td[data-time]')?.getAttribute('data-time');

      if (!lane || !time) {
        cursorBadge.classList.remove('show');
        return;
      }

      calendarEl.querySelectorAll('.fc-timegrid-slot-lane.fc-slot-hover')
        .forEach(el => el.classList.remove('fc-slot-hover'));
      lane.classList.add('fc-slot-hover');

      const scRect = scroller.getBoundingClientRect();
      const laneRect = lane.getBoundingClientRect();
      const top = laneRect.top - scRect.top - 10;

      cursorBadge.textContent = time.slice(0, 5);
      cursorBadge.style.top = `${top}px`;
      cursorBadge.classList.add('show');
    };

    const hide = () => {
      cursorBadge.classList.remove('show');
      calendarEl.querySelectorAll('.fc-timegrid-slot-lane.fc-slot-hover')
        .forEach(el => el.classList.remove('fc-slot-hover'));
    };

    scroller.onmousemove = show;
    scroller.onmouseleave = hide;
  }

  setupCursorBadge();

  // ====== dialog送信 ======
  if (hasDialog) {
    btnCancel.addEventListener('click', () => {
      try { dlg.close(); } catch (_) {}
      pending = null;
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!pending) return;

      clearReserveError();

      const title = rTitle.value.trim();
      const location = rLocation.value.trim();

      if (!title) { showReserveError('予約内容（タイトル）は必須です'); return; }
      if (!location) { showReserveError('場所は必須です'); return; }

      try {
        const res = await fetch('create_event.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            title,
            start: toMysqlDatetime(pending.start),
            end: toMysqlDatetime(pending.end),
            location,
            who_name: rWho.value.trim(),
            description: rDesc.value.trim()
          })
        });

        const text = await res.text();
        let data = null;
        try { data = JSON.parse(text); } catch (_) {}

        if (!res.ok) {
          if (res.status === 409) {
            showReserveError((data && data.error) ? data.error : 'その時間は既に予約があります');
            return;
          }
          if (res.status === 401) {
            showReserveError('ログインが切れました。もう一度ログインしてください。');
            return;
          }
          showReserveError((data && data.error) ? data.error : `登録に失敗しました（HTTP ${res.status}）`);
          return;
        }

        if (data && data.success) {
          calendar.refetchEvents();
          try { dlg.close(); } catch (_) {}
          pending = null;
        } else {
          showReserveError((data && data.error) ? data.error : '登録に失敗しました');
        }

      } catch (err) {
        showReserveError('通信エラーが発生しました');
      }
    });
  }
});
