// ============================================================
//  Cinema Seat Booking — script.js
//  Wires up: trailer play button, date & time pickers,
//  seat selection + price total, booking -> ticket screen,
//  and the barcode generation.
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  /* ---------- 1. Element references ---------- */
  const posterImg      = document.querySelector('.left img');
  const playBtn         = document.querySelector('.left .play');
  const posterVideo     = document.querySelector('.left #posterVideo');
  const bgVideo          = document.querySelector('#bgVideo');

  const dateList         = document.querySelectorAll('.left_card .card_month li');
  const timeList          = document.querySelectorAll('.right_card .card_month li');

  const seats            = document.querySelectorAll('.chair .seat');

  const bookBtn           = document.querySelector('.book_ticket');
  const backBtn           = document.querySelector('.back_ticket');

  const ticketSection     = document.querySelector('.ticket');
  const bookingSection    = document.querySelectorAll('.date_type, .screen, .chair, .details');
  const barcodeSvg         = document.querySelector('#barcode');

  const ticTemplate = document.querySelector('.ticket .tic');
  const ticTemplateHTML = ticTemplate ? ticTemplate.outerHTML : null;

  const MAX_SEATS_PER_TICKET = 2;

  // ---- eSewa ePay v2 (sandbox/UAT) config ----
  const ESEWA_SECRET       = '8gBm/:&EnhH.1/q';
  const ESEWA_PRODUCT_CODE = 'EPAYTEST';
  const ESEWA_FORM_URL     = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
  const ESEWA_RETURN_URL   = window.location.origin + window.location.pathname;
  const ESEWA_STORAGE_KEY  = 'pending_esewa_booking';

  // ---- HMAC-SHA256 signing using the browser's built-in Web Crypto API ----
  async function hmacSha256Base64(message, secret) {
    if (!window.crypto?.subtle) {
      throw new Error(
        'Web Crypto API unavailable — this requires HTTPS (or http://localhost while testing locally).'
      );
    }
    const enc = new TextEncoder();
    const cryptoKey = await crypto.subtle.importKey(
      'raw',
      enc.encode(secret),
      { name: 'HMAC', hash: 'SHA-256' },
      false,
      ['sign']
    );
    const signatureBuffer = await crypto.subtle.sign('HMAC', cryptoKey, enc.encode(message));
    const bytes = new Uint8Array(signatureBuffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) binary += String.fromCharCode(bytes[i]);
    return btoa(binary);
  }

  let currentScreen = 'booking';

  const PRICE_PER_SEAT = 560;

  let selectedSeats = [];
  let selectedDate  = null;
  let selectedTime  = null;
  let activeBarcodeValue = null;

  /* ---------- 2. Hide ticket screen on load ---------- */
  if (ticketSection) ticketSection.style.display = 'none';

  // We initialize selectedDate and selectedTime from DOM on load
  const activeDateEl = document.querySelector('.date_point.h6_active');
  if (activeDateEl) {
    const li = activeDateEl.closest('li');
    const day = li?.querySelector('h6:first-child')?.textContent.trim();
    const num = activeDateEl.textContent.trim();
    const fullDate = activeDateEl.dataset.fulldate;
    selectedDate = { day, num, fullDate };
  }

  const activeTimeEl = document.querySelector('.right_card .card_month li h6.h6_active');
  if (activeTimeEl) {
    selectedTime = activeTimeEl.textContent.trim();
  }

  /* ---------- 3b. Poster preview: image <-> video toggle ---------- */
  if (posterVideo) {
    posterVideo.style.display = 'none';
  }

  if (playBtn && posterVideo && posterImg) {
    playBtn.addEventListener('click', () => {
      if (posterVideo.paused) {
        posterVideo.play();
      } else {
        posterVideo.pause();
      }
    });

    posterVideo.addEventListener('play', () => {
      posterImg.style.display = 'none';
      posterVideo.style.display = 'block';
      playBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
    });

    posterVideo.addEventListener('pause', () => {
      posterVideo.style.display = 'none';
      posterImg.style.display = 'block';
      playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
    });

    posterVideo.addEventListener('ended', () => {
      posterVideo.currentTime = 0;
    });
  }

  /* ---------- 5. Date selection ---------- */
  dateList.forEach(li => {
    li.addEventListener('click', () => {
      dateList.forEach(l => l.querySelector('.date_point')?.classList.remove('h6_active'));
      const point = li.querySelector('.date_point');
      if (point) point.classList.add('h6_active');

      const day  = li.querySelector('h6:first-child')?.textContent.trim();
      const num  = point?.textContent.trim();
      const fullDate = point?.dataset?.fulldate;
      selectedDate = { day, num, fullDate };
      fetchBookedSeats();
    });
  });

  /* ---------- 6. Showtime selection ---------- */
  timeList.forEach(li => {
    li.addEventListener('click', () => {
      timeList.forEach(l => {
        const heads = l.querySelectorAll('h6');
        heads.forEach(h => h.classList.remove('h6_active'));
      });
      const timeHeading = li.querySelectorAll('h6')[1];
      if (timeHeading) timeHeading.classList.add('h6_active');

      selectedTime = timeHeading ? timeHeading.textContent.trim() : null;
      fetchBookedSeats();
    });
  });

  /* ---------- 7. Seat selection ---------- */
  seats.forEach((seat, index) => {
    const row = seat.closest('.row');
    const rowLabel = row?.querySelector('span')?.textContent.trim() || 'A';
    const seatsInRow = Array.from(row.querySelectorAll('.seat'));
    const seatNumber = seatsInRow.indexOf(seat) + 1;

    seat.dataset.row = rowLabel;
    seat.dataset.seatNumber = seatNumber;

    seat.addEventListener('click', () => {
      if (seat.classList.contains('booked')) return;

      seat.classList.toggle('selected');

      if (seat.classList.contains('selected')) {
        selectedSeats.push({ row: rowLabel, seatNumber, el: seat });
      } else {
        selectedSeats = selectedSeats.filter(s => s.el !== seat);
      }

      updateSeatSummary();
    });
  });

  /* ---------- 8. Live summary of selected seats & total price ---------- */
  function updateSeatSummary() {
    let summaryEl = document.querySelector('.seat_summary');
    if (!summaryEl) {
      summaryEl = document.createElement('div');
      summaryEl.className = 'seat_summary';
      const detailsEl = document.querySelector('.details');
      detailsEl?.insertAdjacentElement('afterend', summaryEl);
      summaryEl.style.width = '100%';
      summaryEl.style.textAlign = 'center';
      summaryEl.style.color = '#fff';
      summaryEl.style.fontSize = '12px';
      summaryEl.style.marginTop = '10px';
    }

    if (selectedSeats.length === 0) {
      summaryEl.textContent = '';
      return;
    }

    const seatLabels = selectedSeats
      .map(s => `${s.row}${s.seatNumber}`)
      .join(', ');
    const total = selectedSeats.length * PRICE_PER_SEAT;

    summaryEl.textContent = `Seats: ${seatLabels} — Total: ${total}`;
  }

  /* ---------- 9. Payment screen (eSewa) ---------- */
  const paymentSection = document.createElement('div');
  paymentSection.className = 'payment_screen';
  paymentSection.style.display = 'none';
  paymentSection.style.flexDirection = 'column';
  paymentSection.style.alignItems = 'center';
  paymentSection.style.justifyContent = 'center';
  paymentSection.style.width = '100%';
  paymentSection.style.height = '75%';
  paymentSection.style.marginTop = '20px';
  paymentSection.style.color = '#fff';
  paymentSection.innerHTML = `
    <h5 style="margin-bottom: 25px; letter-spacing: 1px; font-weight: 600;">Select Payment Method</h5>
    <div class="payment_total" style="margin-bottom: 30px; color: skyblue; font-weight: 600; font-size: 15px;"></div>
    <div style="display: flex; flex-direction: column; gap: 15px; width: 80%; max-width: 320px;">
      <button type="button" class="pay_esewa" style="
          display: flex; align-items: center; justify-content: center;
          padding: 14px 20px; border-radius: 10px; border: none;
          background: #60BB46; color: #fff; font-size: 14px; font-weight: 600;
          cursor: pointer; transition: .3s linear;">
        Pay via eSewa
      </button>
      <button type="button" class="pay_simulate" style="
          display: flex; align-items: center; justify-content: center;
          padding: 14px 20px; border-radius: 10px; border: none;
          background: #4A5568; color: #fff; font-size: 14px; font-weight: 600;
          cursor: pointer; transition: .3s linear;">
        <i class="bi bi-magic" style="margin-right: 8px;"></i> Simulate Payment (Testing)
      </button>
    </div>
    <div class="payment_status" style="margin-top: 20px; font-size: 12px; color: rgb(184,184,184,.7);"></div>
  `;
  ticketSection?.insertAdjacentElement('afterend', paymentSection);

  const payEsewaBtn   = paymentSection.querySelector('.pay_esewa');
  const paySimulateBtn= paymentSection.querySelector('.pay_simulate');
  const paymentTotal  = paymentSection.querySelector('.payment_total');
  const paymentStatus = paymentSection.querySelector('.payment_status');

  /* ---------- eSewa ePay v2: signature + form submission ---------- */
  async function payViaEsewa() {
    if (payEsewaBtn) payEsewaBtn.disabled = true;
    if (paymentStatus) paymentStatus.textContent = 'Preparing secure payment...';

    // Get movie ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const movieId = urlParams.get('id');

    const bookingData = {
        movie_id: movieId,
        date: selectedDate.fullDate,
        time: selectedTime,
        seats: selectedSeats.map(s => ({ row: s.row, seatNumber: s.seatNumber }))
    };

    try {
        const response = await fetch('prepare_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(bookingData)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to prepare booking');
        }

        if (paymentStatus) paymentStatus.textContent = 'Redirecting to eSewa...';

        const fields = {
            amount: String(data.total_amount),
            tax_amount: "0",
            total_amount: String(data.total_amount),
            transaction_uuid: data.transaction_uuid,
            product_code: data.product_code,
            product_service_charge: "0",
            product_delivery_charge: "0",
            success_url: ESEWA_RETURN_URL,
            failure_url: ESEWA_RETURN_URL,
            signed_field_names: 'total_amount,transaction_uuid,product_code',
            signature: data.signature
        };

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = ESEWA_FORM_URL;
        form.style.display = 'none';

        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();

    } catch (err) {
        console.error(err);
        if (paymentStatus) paymentStatus.textContent = '';
        if (payEsewaBtn) payEsewaBtn.disabled = false;
        alert(err.message || 'Could not prepare the payment. Please try again.');
    }
  }

  async function payViaSimulation() {
    if (paySimulateBtn) paySimulateBtn.disabled = true;
    if (paymentStatus) paymentStatus.textContent = 'Simulating payment on server...';

    const urlParams = new URLSearchParams(window.location.search);
    const movieId = urlParams.get('id');

    const bookingData = {
        movie_id: movieId,
        date: selectedDate.fullDate,
        time: selectedTime,
        seats: selectedSeats.map(s => ({ row: s.row, seatNumber: s.seatNumber }))
    };

    try {
        const response = await fetch('prepare_simulation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(bookingData)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to simulate booking');
        }

        if (paymentStatus) paymentStatus.textContent = 'Payment successful! Redirecting to ticket...';
        window.location.href = `view_ticket.php?booking_id=${data.booking_id}&show_ticket=1`;

    } catch (err) {
        console.error(err);
        if (paymentStatus) paymentStatus.textContent = '';
        if (paySimulateBtn) paySimulateBtn.disabled = false;
        alert(err.message || 'Simulation failed.');
    }
  }

  payEsewaBtn?.addEventListener('click', payViaEsewa);
  paySimulateBtn?.addEventListener('click', payViaSimulation);

  /* ---------- 10. Book button: validate + go to payment screen ---------- */
  bookBtn?.addEventListener('click', () => {
    if (selectedSeats.length === 0) {
      alert('Please select at least one seat before booking.');
      return;
    }
    if (!selectedDate) {
      alert('Please select a date.');
      return;
    }
    if (!selectedTime) {
      alert('Please select a showtime.');
      return;
    }

    if (paymentTotal) {
      const total = selectedSeats.length * PRICE_PER_SEAT;
      paymentTotal.textContent = `${selectedSeats.length} seat(s) — Total: ${total}`;
    }

    showScreen('payment');
  });

  /* ---------- 11. Back button: step backward through the screens ---------- */
  backBtn?.addEventListener('click', () => {
    if (currentScreen === 'ticket') {
      window.location.href = '../profile/profile.php#tab-bookings';
    } else if (currentScreen === 'payment') {
      showScreen('booking');
    } else {
      showScreen('booking');
    }
  });

  /* ---------- 12. Helper: split an array into chunks of a given size ---------- */
  function chunkArray(arr, size) {
    const chunks = [];
    for (let i = 0; i < arr.length; i += size) {
      chunks.push(arr.slice(i, i + size));
    }
    return chunks;
  }

  /* ---------- 13. Build one ticket card per group of up to 5 seats ---------- */
  function renderTicket() {
    if (!ticTemplateHTML || !ticketSection) return;

    ticketSection.innerHTML = '';

    const seatGroups = chunkArray(selectedSeats, MAX_SEATS_PER_TICKET);

    seatGroups.forEach((group, ticketIndex) => {
      const wrapper = document.createElement('div');
      wrapper.innerHTML = ticTemplateHTML;
      const ticEl = wrapper.firstElementChild;

      const rows = [...new Set(group.map(s => s.row))].join(', ');
      const seatNums = group.map(s => s.seatNumber).join(', ');
      const total = group.length * PRICE_PER_SEAT;

      const cards = ticEl.querySelectorAll('.barcode .card h6');
      if (cards.length >= 4) {
        cards[0].textContent = `Row ${rows}`;
        cards[1].textContent = selectedDate ? `${selectedDate.day} ${selectedDate.num}` : '';
        cards[2].textContent = `Seat ${seatNums}`;
        cards[3].textContent = selectedTime || '';
      }

      const seatDet = ticEl.querySelectorAll('.tic_details .seat_det');
      if (seatDet.length >= 2) {
        const cells = seatDet[1].querySelectorAll('.seat_cr');
        if (cells.length >= 4) {
          cells[0].querySelectorAll('h6')[1].textContent = rows;
          cells[1].querySelectorAll('h6')[1].textContent = seatNums;
          cells[2].querySelectorAll('h6')[1].textContent = selectedDate?.num || '';
          cells[3].querySelectorAll('h6')[1].textContent = selectedTime || '';
        }
      }

      const svg = ticEl.querySelector('#barcode');
      if (svg) {
        svg.id = `barcode-${ticketIndex}`;
      }

      const priceEl = ticEl.querySelector('.tic_details h5.pvr');
      if (priceEl) priceEl.dataset.total = total;

      if (ticketIndex > 0) {
        ticEl.style.marginTop = '15px';
      }

      ticketSection.appendChild(ticEl);

      const code = activeBarcodeValue || `J${Date.now().toString().slice(-8)}${ticketIndex}`;
      if (svg && window.JsBarcode) {
        JsBarcode(svg, code);
      }
    });
  }

  /* ---------- 14. Screen toggling ---------- */
  function showScreen(screen) {
    currentScreen = screen;

    bookingSection.forEach(el => el && (el.style.display = screen === 'booking' ? '' : 'none'));
    if (bookBtn) bookBtn.style.display = screen === 'booking' ? 'flex' : 'none';

    if (paymentSection) paymentSection.style.display = screen === 'payment' ? 'flex' : 'none';

    if (ticketSection) ticketSection.style.display = screen === 'ticket' ? 'block' : 'none';

    if (backBtn) backBtn.style.display = screen === 'booking' ? 'none' : 'flex';
  }

  showScreen('booking');

  // Load booked seats on page init
  fetchBookedSeats();

  // Check if we were redirected to show a ticket
  checkForRedirectedTicket();

  // ---- Fetch booked seats dynamically from DB ----
  async function fetchBookedSeats() {
    if (!selectedDate?.fullDate || !selectedTime) return;

    const urlParams = new URLSearchParams(window.location.search);
    const movieId = urlParams.get('id');

    try {
      const response = await fetch(`get_booked_seats.php?movie_id=${movieId}&date=${selectedDate.fullDate}&time=${selectedTime}`);
      const data = await response.json();
      if (data.success) {
        // Reset all seats
        seats.forEach(seat => {
          seat.classList.remove('booked');
          seat.classList.remove('selected');
        });
        selectedSeats = [];
        updateSeatSummary();

        // Mark fetched seats as booked
        data.booked_seats.forEach(bs => {
          const seatEl = Array.from(seats).find(s => s.dataset.row === bs.row && parseInt(s.dataset.seatNumber) === parseInt(bs.seat_number));
          if (seatEl) {
            seatEl.classList.add('booked');
          }
        });
      }
    } catch (err) {
      console.error('Error fetching booked seats:', err);
    }
  }

  // ---- Check for redirected ticket info ----
  async function checkForRedirectedTicket() {
    const urlParams = new URLSearchParams(window.location.search);
    const showTicket = urlParams.get('show_ticket');
    const bookingId = urlParams.get('booking_id');

    if (showTicket === '1' && bookingId) {
      try {
        const response = await fetch(`get_booking_details.php?booking_id=${bookingId}`);
        const data = await response.json();
        if (data.success && data.booking) {
          selectedSeats = data.booking.seats;
          selectedDate = data.booking.date;
          selectedTime = data.booking.time;
          activeBarcodeValue = data.booking.barcode;

          renderTicket();
          showScreen('ticket');
        }
      } catch (err) {
        console.error('Error loading booking details:', err);
      }
    }
  }

});