<?php
// ============================================================
//  booking/view_ticket.php
//  Renders a printable ticket page using the original e-ticket
//  template from booking.php / style.css.
//  Supports grouping 2 seats per ticket if total seats > 1.
// ============================================================
session_start();

// Accessible by logged-in user OR admin
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require '../database/db.php';

$bookingId = (int)($_GET['booking_id'] ?? 0);
if (!$bookingId) {
    die('Missing booking ID.');
}

try {
    // Fetch booking details
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.barcode_value, b.total_amount, b.status, b.user_id,
               st.show_date, st.show_time,
               m.title, m.poster_image,
               h.hall_name
        FROM bookings b
        JOIN showtimes st ON b.showtime_id = st.showtime_id
        JOIN movies m ON st.movie_id = m.movie_id
        JOIN halls h ON st.hall_id = h.hall_id
        WHERE b.booking_id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        die('Booking not found.');
    }

    // Authorization: User must own the booking, OR must be an admin
    if (!isset($_SESSION['admin_id']) && $_SESSION['user_id'] != $booking['user_id']) {
        die('Access denied.');
    }

    // Fetch seats
    $stmtSeats = $pdo->prepare("
        SELECT s.seat_row, s.seat_number
        FROM booking_seats bs
        JOIN seats s ON bs.seat_id = s.seat_id
        WHERE bs.booking_id = ?
        ORDER BY s.seat_row, s.seat_number
    ");
    $stmtSeats->execute([$bookingId]);
    $seats = $stmtSeats->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}

// Grouping logic:
// "if 1 seat booked the 1 ticket and if more then 1 the each 2 seat ticket =1 ticket"
$seatGroups = [];
if (count($seats) === 1) {
    $seatGroups[] = [$seats[0]];
} else {
    $seatGroups = array_chunk($seats, 2);
}

// Back URL determination
$backUrl = '../profile/profile.php#tab-bookings';
if (isset($_SESSION['admin_id'])) {
    $backUrl = '../admin/user_details.php?id=' . $booking['user_id'];
}

// Poster image path
$posterPath = '';
if (!empty($booking['poster_image'])) {
    $posterPath = '../' . $booking['poster_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBooking | View Ticket</title>
    <!-- Use the same base styles as booking.php -->
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        /* ── View Ticket Page Overrides ── */
        body {
            width: 100%;
            height: auto;
            min-height: 100vh;
            display: block;
            padding: 30px 20px;
            background: #1f2025;
            overflow-y: auto;
        }

        .header-bar {
            max-width: 850px;
            margin: 0 auto 25px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: .2s ease;
            font-family: 'Poppins', sans-serif;
        }
        .btn-back {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.2);
        }
        .btn-print {
            background: #fd6565;
            color: #fff;
        }
        .btn-print:hover {
            background: #e05353;
        }

        .tickets-wrapper {
            max-width: 850px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* ── Override .book styles for standalone ticket view ── */
        .tickets-wrapper .book {
            width: 100% !important;
            height: auto !important;
            display: flex !important;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .tickets-wrapper .book .left {
            display: none !important;
        }

        .tickets-wrapper .book .right {
            width: 100% !important;
            height: auto !important;
            padding: 0 !important;
            background: transparent !important;
        }

        /* Remove the ::before and ::after pseudo-elements from .right */
        .tickets-wrapper .book .right::before,
        .tickets-wrapper .book .right::after {
            display: none !important;
        }

        /* ── Ticket card: matches the original .tic template ── */
        .tickets-wrapper .book .right .ticket {
            display: block !important;
            width: 100%;
            height: auto !important;
            margin-top: 0 !important;
            overflow: visible !important;
        }

        .tickets-wrapper .book .right .ticket .tic {
            width: 100% !important;
            height: 215px !important;
            display: flex !important;
            overflow: hidden;
            border-radius: 0;
            margin-top: 0 !important;
        }

        /* Barcode section (left side of the ticket) */
        .tickets-wrapper .book .right .ticket .tic .barcode {
            width: 300px !important;
            min-width: 300px;
            height: 100% !important;
            padding: 0 20px;
            background: white !important;
            position: relative;
        }

        .tickets-wrapper .book .right .ticket .tic .barcode .card {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 10px;
        }
        .tickets-wrapper .book .right .ticket .tic .barcode .card:nth-child(1) {
            margin-top: 15px;
        }
        .tickets-wrapper .book .right .ticket .tic .barcode .card h6 {
            width: 60%;
            text-align: left;
            font-weight: 600;
            color: #000;
            font-size: 12px;
        }
        .tickets-wrapper .book .right .ticket .tic .barcode h5 {
            width: 100%;
            text-align: center;
            color: #000;
            font-size: 12px;
            margin-top: 5px;
        }
        .tickets-wrapper .book .right .ticket .tic .barcode .barcode-code {
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: #333;
            margin-top: 2px;
        }

        /* Ticket details section (right side — movie poster background) */
        .tickets-wrapper .book .right .ticket .tic .tic_details {
            width: 100% !important;
            height: 100% !important;
            position: relative;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
        }

        .tickets-wrapper .book .right .ticket .tic .tic_details .type {
            position: absolute;
            left: 20px;
            top: 7px;
            font-weight: 600;
            color: white;
            font-size: 14px;
        }
        .tickets-wrapper .book .right .ticket .tic .tic_details .pvr {
            position: absolute;
            right: 20px;
            top: 7px;
            font-weight: 600;
            color: skyblue;
            font-size: 14px;
        }
        .tickets-wrapper .book .right .ticket .tic .tic_details .pvr span {
            color: white;
        }
        .tickets-wrapper .book .right .ticket .tic .tic_details h1 {
            color: white;
            position: absolute;
            left: 20px;
            bottom: 80px;
            font-size: 28px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.7);
        }
        .tickets-wrapper .book .right .ticket .tic .tic_details .seat_det {
            position: absolute;
            left: 0;
            bottom: 8px;
            width: 100%;
            height: 60px;
            background: linear-gradient(270deg, transparent, rgb(0,0,0,.3));
            display: flex;
            align-items: center;
            padding-left: 25px;
        }
        .tickets-wrapper .book .right .ticket .tic .tic_details .seat_det .seat_cr {
            width: 100px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
        }
        .tickets-wrapper .book .right .ticket .tic .tic_details .seat_det .seat_cr h6:nth-child(1) {
            font-size: 11px;
            font-weight: 600;
        }
        .tickets-wrapper .book .right .ticket .tic .tic_details .seat_det .seat_cr h6:nth-child(2) {
            font-size: 20px;
            font-weight: 600;
        }
        .tickets-wrapper .book .right .ticket .tic .tic_details .seat_det .seat_cr h6:nth-child(2) sub {
            font-size: 11px;
            font-weight: 600;
        }

        /* ── Print styles ── */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #fff;
                padding: 10px;
            }
            .tickets-wrapper .book .right .ticket .tic .barcode {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .tickets-wrapper .book .right .ticket .tic .tic_details {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .tickets-wrapper .book .right .ticket .tic .tic_details .seat_det {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .tickets-wrapper .book {
                page-break-inside: avoid;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

    <div class="header-bar no-print">
        <a href="<?php echo $backUrl; ?>" class="btn-action btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fa-solid fa-print"></i> Print Ticket
        </button>
    </div>

    <div class="tickets-wrapper">
        <?php foreach ($seatGroups as $index => $group): 
            $rows = implode(', ', array_unique(array_column($group, 'seat_row')));
            $seatNums = implode(', ', array_column($group, 'seat_number'));
            
            $ts = strtotime($booking['show_date']);
            $dateDay = date('j', $ts);
            $dateMonth = date('M', $ts);
            $dateFormatted = date('D j', $ts);
            $timeFormatted = date('H:i', strtotime($booking['show_time']));
            $timeAmPm = date('a', strtotime($booking['show_time']));
            
            $ticketCode = $booking['barcode_value'] . '-' . ($index + 1);

            // Use movie poster if available, otherwise fallback
            $bgStyle = '';
            if ($posterPath) {
                $bgStyle = "background: url('" . htmlspecialchars($posterPath) . "') no-repeat center / cover;";
            } else {
                $bgStyle = "background: url('../img/bg.png') no-repeat center -35px / cover;";
            }
        ?>
            <div class="book">
                <div class="right">
                    <div class="ticket">
                        <div class="tic">
                            <!-- Left: Barcode panel (white) -->
                            <div class="barcode">
                                <div class="card">
                                    <h6>Row <?php echo htmlspecialchars($rows); ?></h6>
                                    <h6><?php echo htmlspecialchars($dateFormatted); ?></h6>
                                </div>
                                <div class="card">
                                    <h6>Seat <?php echo htmlspecialchars($seatNums); ?></h6>
                                    <h6><?php echo htmlspecialchars($timeFormatted); ?></h6>
                                </div>
                                <svg class="barcode-svg" data-code="<?php echo htmlspecialchars($ticketCode); ?>"></svg>
                                <div class="barcode-code"><?php echo htmlspecialchars($ticketCode); ?></div>
                                <h5>CINEBOOKING</h5>
                            </div>

                            <!-- Right: Movie poster + details -->
                            <div class="tic_details" style="<?php echo $bgStyle; ?>">
                                <div class="type">4DX</div>
                                <h5 class="pvr"><span>Cine</span>Booking</h5>
                                <h1><?php echo htmlspecialchars($booking['title']); ?></h1>
                                <div class="seat_det">
                                    <div class="seat_cr">
                                        <h6>ROW</h6>
                                        <h6><?php echo htmlspecialchars($rows); ?></h6>
                                    </div>
                                    <div class="seat_cr">
                                        <h6>SEAT</h6>
                                        <h6><?php echo htmlspecialchars($seatNums); ?></h6>
                                    </div>
                                    <div class="seat_cr">
                                        <h6>DATE</h6>
                                        <h6><?php echo $dateDay; ?><sub><?php echo $dateMonth; ?></sub></h6>
                                    </div>
                                    <div class="seat_cr">
                                        <h6>TIME</h6>
                                        <h6><?php echo date('g:i', strtotime($booking['show_time'])); ?><sub><?php echo $timeAmPm; ?></sub></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- JsBarcode library for barcode generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.12.1/JsBarcode.all.js"
        integrity="sha512-iKSej9nrLjdo6RKseKJcAZgTLj6ESwnUjj/vEFhuDhNTzULrk5RBPWeG2YIVWcI6hAIEccsY9Qf6+g39jpDfnw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const barSvgList = document.querySelectorAll('.barcode-svg');
            barSvgList.forEach(svg => {
                const code = svg.dataset.code;
                if (code && window.JsBarcode) {
                    JsBarcode(svg, code, {
                        width: 1.2,
                        height: 50,
                        displayValue: false,
                        margin: 5
                    });
                }
            });
        });
    </script>
</body>
</html>
