<?php
require_once 'config.php';

// Get match ID from URL parameter
$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;

if ($match_id <= 0) {
    header('Location: tiket.php');
    exit;
}

// Fetch match details
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id_pertandingan,
            p.deskripsi_pertandingan,
            p.jadwal,
            p.jam,
            tr.jumlah as jumlah_reguler,
            tr.harga as harga_reguler,
            tv.jumlah as jumlah_vip,
            tv.harga as harga_vip,
            t1.nama as nama_tim1,
            t1.logo as logo_tim1,
            t2.nama as nama_tim2,
            t2.logo as logo_tim2
        FROM pertandingan p
        LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
        LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
        LEFT JOIN tim t1 ON p.id_tim1 = t1.id_tim
        LEFT JOIN tim t2 ON p.id_tim2 = t2.id_tim
        WHERE p.id_pertandingan = ?
    ");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        header('Location: tiket.php');
        exit;
    }
} catch(PDOException $e) {
    header('Location: tiket.php');
    exit;
}

// Function to format Indonesian date
function formatIndonesianDate($date) {
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    $timestamp = strtotime($date);
    $dayName = $days[date('w', $timestamp)];
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$dayName, $day $month $year";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Tiket - Dark System</title>
    <link rel="stylesheet" href="tiket.css">
    <link rel="stylesheet" href="pemesanan.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo"><span class="dark">DARK</span><span class="system">SYSTEM</span></div>
        <ul class="nav-links">
            <li><a href="home.php">HOME</a></li>
            <li><a href="jadwal.php">JADWAL</a></li>
            <li><a href="tiket.php">TIKET</a></li>
        </ul>
    </nav>

    <div class="booking-container">
        <h1 style="text-align: center; color: #0a122a; margin-bottom: 30px;">Pemesanan Tiket</h1>
        
        <div class="match-info">
            <div class="match-teams">
                <div class="team-info">
                    <?php if (!empty($match['logo_tim1']) && file_exists($match['logo_tim1'])): ?>
                        <img src="<?php echo htmlspecialchars($match['logo_tim1']); ?>" alt="<?php echo htmlspecialchars($match['nama_tim1']); ?> logo" />
                    <?php else: ?>
                        <img src="img/default-logo.png" alt="Default logo" />
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($match['nama_tim1']); ?></span>
                </div>
                <div class="vs">VS</div>
                <div class="team-info">
                    <?php if (!empty($match['logo_tim2']) && file_exists($match['logo_tim2'])): ?>
                        <img src="<?php echo htmlspecialchars($match['logo_tim2']); ?>" alt="<?php echo htmlspecialchars($match['nama_tim2']); ?> logo" />
                    <?php else: ?>
                        <img src="img/default-logo.png" alt="Default logo" />
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($match['nama_tim2']); ?></span>
                </div>
            </div>
            <div class="match-details">
                <p><strong><?php echo formatIndonesianDate($match['jadwal']); ?></strong></p>
                <p><?php echo date('H:i', strtotime($match['jam'])); ?> WIB</p>
            </div>
        </div>

        <form id="bookingForm" action="process_booking.php" method="POST">
            <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
            
            <div class="form-group">
                <label for="nama">Nama Lengkap *</label>
                <input type="text" id="nama" name="nama" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="no_telepon">Nomor Telepon *</label>
                <input type="tel" id="no_telepon" name="no_telepon" required>
            </div>
            
            <h3 style="color: #0a122a; margin-bottom: 20px;">Pilih Jenis Tiket</h3>
            
            <div class="ticket-selection">
                <div class="ticket-type" onclick="selectTicket('reguler')" id="ticket-reguler">
                    <h3>Tiket Reguler</h3>
                    <div class="ticket-price">Rp <?php echo number_format($match['harga_reguler'], 0, ',', '.'); ?></div>
                    <div class="ticket-available">Tersedia: <?php echo $match['jumlah_reguler']; ?> tiket</div>
                    <div class="quantity-selector" style="display: none;">
                        <button type="button" class="quantity-btn" onclick="changeQuantity('reguler', -1)">-</button>
                        <input type="number" class="quantity-input" id="qty-reguler" value="1" min="1" max="<?php echo $match['jumlah_reguler']; ?>" onchange="updateTotal()">
                        <button type="button" class="quantity-btn" onclick="changeQuantity('reguler', 1)">+</button>
                    </div>
                </div>
                
                <div class="ticket-type" onclick="selectTicket('vip')" id="ticket-vip">
                    <h3>Tiket VIP</h3>
                    <div class="ticket-price">Rp <?php echo number_format($match['harga_vip'], 0, ',', '.'); ?></div>
                    <div class="ticket-available">Tersedia: <?php echo $match['jumlah_vip']; ?> tiket</div>
                    <div class="quantity-selector" style="display: none;">
                        <button type="button" class="quantity-btn" onclick="changeQuantity('vip', -1)">-</button>
                        <input type="number" class="quantity-input" id="qty-vip" value="1" min="1" max="<?php echo $match['jumlah_vip']; ?>" onchange="updateTotal()">
                        <button type="button" class="quantity-btn" onclick="changeQuantity('vip', 1)">+</button>
                    </div>
                </div>
            </div>
            
            <div class="total-section" id="totalSection" style="display: none;">
                <div class="total-row">
                    <span>Jenis Tiket:</span>
                    <span id="selectedTicketType">-</span>
                </div>
                <div class="total-row">
                    <span>Jumlah Tiket:</span>
                    <span id="selectedQuantity">-</span>
                </div>
                <div class="total-row">
                    <span>Harga per Tiket:</span>
                    <span id="ticketPrice">-</span>
                </div>
                <div class="total-row total-final">
                    <span>Total Pembayaran:</span>
                    <span id="totalPrice">Rp 0</span>
                </div>
            </div>
            
            <input type="hidden" name="ticket_type" id="ticketType">
            <input type="hidden" name="quantity" id="quantity">
            <input type="hidden" name="total_price" id="totalPriceValue">
            
            <button type="submit" class="submit-btn" id="submitBtn" disabled>Lanjut ke Pembayaran</button>
        </form>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Dark System. All rights reserved.</p>
    </footer>

    <script>
        let selectedType = '';
        const prices = {
            'reguler': <?php echo $match['harga_reguler']; ?>,
            'vip': <?php echo $match['harga_vip']; ?>
        };
        const maxQuantity = {
            'reguler': <?php echo $match['jumlah_reguler']; ?>,
            'vip': <?php echo $match['jumlah_vip']; ?>
        };

        function selectTicket(type) {
            // Remove previous selection
            document.querySelectorAll('.ticket-type').forEach(el => {
                el.classList.remove('selected');
                el.querySelector('.quantity-selector').style.display = 'none';
            });
            
            // Select new ticket type
            selectedType = type;
            document.getElementById('ticket-' + type).classList.add('selected');
            document.getElementById('ticket-' + type).querySelector('.quantity-selector').style.display = 'flex';
            
            // Update form
            document.getElementById('ticketType').value = type;
            
            updateTotal();
        }

        function changeQuantity(type, change) {
            const input = document.getElementById('qty-' + type);
            let newValue = parseInt(input.value) + change;
            
            if (newValue < 1) newValue = 1;
            if (newValue > maxQuantity[type]) newValue = maxQuantity[type];
            
            input.value = newValue;
            updateTotal();
        }

        function updateTotal() {
            if (!selectedType) return;
            
            const quantity = parseInt(document.getElementById('qty-' + selectedType).value);
            const price = prices[selectedType];
            const total = quantity * price;
            
            // Update display
            document.getElementById('selectedTicketType').textContent = selectedType === 'reguler' ? 'Reguler' : 'VIP';
            document.getElementById('selectedQuantity').textContent = quantity + ' tiket';
            document.getElementById('ticketPrice').textContent = 'Rp ' + price.toLocaleString('id-ID');
            document.getElementById('totalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');
            
            // Update form values
            document.getElementById('quantity').value = quantity;
            document.getElementById('totalPriceValue').value = total;
            
            // Show total section and enable submit
            document.getElementById('totalSection').style.display = 'block';
            document.getElementById('submitBtn').disabled = false;
        }

        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            if (!selectedType) {
                e.preventDefault();
                alert('Silakan pilih jenis tiket terlebih dahulu!');
                return;
            }
            
            const nama = document.getElementById('nama').value.trim();
            const email = document.getElementById('email').value.trim();
            const noTelepon = document.getElementById('no_telepon').value.trim();
            
            if (!nama || !email || !noTelepon) {
                e.preventDefault();
                alert('Silakan lengkapi semua data yang diperlukan!');
                return;
            }
        });
    </script>
</body>
</html>
