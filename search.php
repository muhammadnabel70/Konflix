<?php
session_start();
include 'includes/db.php';
$current_page = 'search';

// Ambil semua Genre untuk tombol filter
$genres = mysqli_query($conn, "SELECT * FROM genres ORDER BY name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cari - KONFLIX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS KHUSUS SEARCH */
        .search-header {
            padding: 40px 60px 20px 60px;
            background: linear-gradient(to bottom, #1a1d29, transparent);
            position: sticky; top: 0; z-index: 50; backdrop-filter: blur(10px);
        }
        
        .search-input-wrapper {
            position: relative; width: 100%; max-width: 800px;
        }
        .search-input {
            width: 100%; padding: 20px 60px; font-size: 2rem;
            background: #252833; border: none; border-radius: 8px;
            color: white; outline: none; font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: 0.3s;
        }
        .search-input:focus { background: #2f3340; box-shadow: 0 0 0 2px #E50914; }
        .search-icon {
            position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
            font-size: 24px; color: #aaa;
        }

        /* GENRE PILLS */
        .genre-filter {
            display: flex; gap: 10px; overflow-x: auto; padding: 20px 60px;
            margin-bottom: 20px; scrollbar-width: none;
        }
        .genre-filter::-webkit-scrollbar { display: none; }
        
        .genre-pill {
            background: #333; color: #ccc; padding: 8px 20px; border-radius: 20px;
            cursor: pointer; white-space: nowrap; font-size: 14px; transition: 0.2s;
            border: 1px solid transparent;
        }
        .genre-pill:hover { background: #444; color: white; }
        .genre-pill.active { background: #E50914; color: white; border-color: #E50914; }

        /* HASIL PENCARIAN */
        #searchResults { padding-top: 20px; min-height: 400px; }
        
        .no-result {
            text-align: center; margin-top: 80px; color: #555;
        }
        .no-result i { font-size: 60px; margin-bottom: 20px; }
        
        /* Hover Effect pada Grid Item */
        .grid-item .item-overlay {
            position: absolute; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.7); display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            opacity: 0; transition: 0.3s;
        }
        .grid-item:hover .item-overlay { opacity: 1; }
        .play-icon {
            font-size: 40px; color: white; background: #E50914;
            width: 60px; height: 60px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 10px; transform: scale(0.8); transition: 0.3s;
        }
        .grid-item:hover .play-icon { transform: scale(1); }
        .item-title { font-weight: bold; padding: 0 10px; text-align: center; font-size: 14px; }
        
        /* Animasi Masuk */
        .animate-in { animation: fadeInUp 0.4s ease forwards; opacity: 0; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="search-header">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchBox" class="search-input" placeholder="Judul film, series, atau olahraga..." autofocus autocomplete="off">
            </div>
        </div>

        <div class="genre-filter">
            <div class="genre-pill active" onclick="filterGenre(0, this)">Semua</div>
            <?php while($g = mysqli_fetch_assoc($genres)): ?>
                <div class="genre-pill" onclick="filterGenre(<?php echo $g['id']; ?>, this)">
                    <?php echo $g['name']; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <div id="searchResults">
            <div class="no-result" style="margin-top:50px;">
                <i class="fa-solid fa-film"></i>
                <h3>Mulai Menjelajah</h3>
                <p>Ketik judul atau pilih genre di atas.</p>
            </div>
        </div>

    </div>
</div>

<script>
    let currentGenre = 0;
    let typingTimer;

    const searchBox = document.getElementById('searchBox');
    const resultDiv = document.getElementById('searchResults');

    // Event Listener: Ketik di Search Box
    searchBox.addEventListener('keyup', () => {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(performSearch, 300); // Delay 300ms agar tidak spam request
    });

    // Fungsi Filter Genre
    function filterGenre(genreId, element) {
        // Update UI tombol aktif
        document.querySelectorAll('.genre-pill').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        
        currentGenre = genreId;
        performSearch();
    }

    // Fungsi Utama Search (AJAX)
    function performSearch() {
        const query = searchBox.value;
        
        // Jika kosong dan genre semua -> Reset
        if (query === '' && currentGenre === 0) {
            resultDiv.innerHTML = `
                <div class="no-result" style="margin-top:50px;">
                    <i class="fa-solid fa-film"></i>
                    <h3>Mulai Menjelajah</h3>
                    <p>Ketik judul atau pilih genre di atas.</p>
                </div>`;
            return;
        }

        // Kirim Data ke Backend
        const formData = new FormData();
        formData.append('query', query);
        if(currentGenre !== 0) formData.append('genre', currentGenre);

        fetch('search_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            resultDiv.innerHTML = data;
        })
        .catch(error => console.error('Error:', error));
    }
</script>

</body>
</html>