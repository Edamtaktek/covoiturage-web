<?php
// Déterminer si on est dans le dossier pages/ ou à la racine
$current_page = basename($_SERVER['PHP_SELF']);
$in_pages_dir = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
$in_admin_dir = (strpos($_SERVER['PHP_SELF'], '/pages/admin/') !== false);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>Covoiturage</h1>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="<?php echo $in_admin_dir ? '../../index.php' : ($in_pages_dir ? '../index.php' : 'index.php'); ?>" 
                   class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    Accueil
                </a>
            </li>
            <li>
                <a href="<?php echo $in_admin_dir ? '../search_rides.php' : ($in_pages_dir ? 'search_rides.php' : 'pages/search_rides.php'); ?>" 
                   class="<?php echo ($current_page == 'search_rides.php') ? 'active' : ''; ?>">
                    Chercher un trajet
                </a>
            </li>
            <li>
                <a href="<?php echo $in_admin_dir ? '../create_ride.php' : ($in_pages_dir ? 'create_ride.php' : 'pages/create_ride.php'); ?>" 
                   class="<?php echo ($current_page == 'create_ride.php') ? 'active' : ''; ?>">
                    Proposer un trajet
                </a>
            </li>
            
            <?php if (isLoggedIn()): ?>
                <li>
                    <a href="<?php echo $in_admin_dir ? '../dashboard.php' : ($in_pages_dir ? 'dashboard.php' : 'pages/dashboard.php'); ?>" 
                       class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="<?php echo $in_admin_dir ? '../cart.php' : ($in_pages_dir ? 'cart.php' : 'pages/cart.php'); ?>"
                       class="<?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>">
                        Panier
                    </a>
                </li>
                
                <?php if (isAdmin()): ?>
                    <li class="admin-section">
                        <hr style="border-color: var(--border-color); margin: 15px 30px;">
                        <span style="display: block; padding: 5px 30px; color: var(--text-light); font-size: 12px; text-transform: uppercase; font-weight: 600;">Administration</span>
                    </li>
                    <li>
                        <a href="<?php echo $in_admin_dir ? 'dashboard.php' : ($in_pages_dir ? 'admin/dashboard.php' : 'pages/admin/dashboard.php'); ?>"
                           class="admin-link <?php echo ($current_page == 'dashboard.php' && $in_admin_dir) ? 'active' : ''; ?>">
                            Tableau Admin
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $in_admin_dir ? 'users.php' : ($in_pages_dir ? 'admin/users.php' : 'pages/admin/users.php'); ?>"
                           class="admin-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                            Utilisateurs
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $in_admin_dir ? 'rides.php' : ($in_pages_dir ? 'admin/rides.php' : 'pages/admin/rides.php'); ?>"
                           class="admin-link <?php echo ($current_page == 'rides.php') ? 'active' : ''; ?>">
                            Trajets
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo $in_admin_dir ? '../logout.php' : ($in_pages_dir ? 'logout.php' : 'pages/logout.php'); ?>" 
               style="color: var(--accent-color); text-decoration: none; font-weight: 500;">
                Déconnexion
            </a>
        <?php else: ?>
            <a href="<?php echo $in_admin_dir ? '../login.php' : ($in_pages_dir ? 'login.php' : 'pages/login.php'); ?>" 
               style="color: var(--secondary-color); text-decoration: none; font-weight: 500; display: block; margin-bottom: 10px;">
                Connexion
            </a>
            <a href="<?php echo $in_admin_dir ? '../register.php' : ($in_pages_dir ? 'register.php' : 'pages/register.php'); ?>" 
               style="color: var(--text-light); text-decoration: none; font-weight: 500;">
                Inscription
            </a>
        <?php endif; ?>
    </div>
</aside>
