<div class="sidebar">
    <a href="index.php" class="sidebar-logo" style="margin-bottom: 40px;">
        <img src="assets/img/logo_konflix.png" alt="KONFLIX" style="width: 80px; height: auto;">
    </a>



    <div class="nav-menu">
        <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>">
            <i class="fa-solid fa-circle-user"></i>
            <span>Profil</span>
        </a>

        <a href="search.php" class="nav-item <?php echo ($current_page == 'search') ? 'active' : ''; ?>">
            <i class="fa-solid fa-magnifying-glass"></i>
            <span>Cari</span>
        </a>
        
        <a href="index.php" class="nav-item <?php echo ($current_page == 'home') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>

        <a href="movies.php" class="nav-item <?php echo ($current_page == 'movies') ? 'active' : ''; ?>">
            <i class="fa-solid fa-film"></i>
            <span>Film</span>
        </a>

        <a href="series.php" class="nav-item <?php echo ($current_page == 'series') ? 'active' : ''; ?>">
            <i class="fa-solid fa-tv"></i>
            <span>Series</span>
        </a>

        <a href="sports.php" class="nav-item <?php echo ($current_page == 'sports') ? 'active' : ''; ?>">
            <i class="fa-solid fa-trophy"></i>
            <span>Olahraga</span>
        </a>
        
    </div>
</div>