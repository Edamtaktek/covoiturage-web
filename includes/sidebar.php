<?php
// Déterminer si on est dans le dossier pages/ ou à la racine
$current_page = basename($_SERVER['PHP_SELF']);
$in_pages_dir = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>Covoiturage</h1>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="<?php echo $in_pages_dir ? '../index.php' : 'index.php'; ?>" 
                   class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    Accueil
                </a>
            </li>
            <li>
                <a href="<?php echo $in_pages_dir ? 'search_rides.php' : 'pages/search_rides.php'; ?>" 
                   class="<?php echo ($current_page == 'search_rides.php') ? 'active' : ''; ?>">
                    Chercher un trajet
                </a>
            </li>
            <li>
                <a href="<?php echo $in_pages_dir ? 'create_ride.php' : 'pages/create_ride.php'; ?>" 
                   class="<?php echo ($current_page == 'create_ride.php') ? 'active' : ''; ?>">
                    Proposer un trajet
                </a>
            </li>
            
            <?php if (isLoggedIn()): ?>
                <li>
                    <a href="<?php echo $in_pages_dir ? 'dashboard.php' : 'pages/dashboard.php'; ?>" 
                       class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="<?php echo $in_pages_dir ? 'cart.php' : 'pages/cart.php'; ?>"
                       class="<?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>">
                        Panier
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo $in_pages_dir ? 'logout.php' : 'pages/logout.php'; ?>" 
               style="color: var(--accent-color); text-decoration: none; font-weight: 500;">
                Déconnexion
            </a>
        <?php else: ?>
            <a href="<?php echo $in_pages_dir ? 'login.php' : 'pages/login.php'; ?>" 
               style="color: var(--secondary-color); text-decoration: none; font-weight: 500; display: block; margin-bottom: 10px;">
                Connexion
            </a>
            <a href="<?php echo $in_pages_dir ? 'register.php' : 'pages/register.php'; ?>" 
               style="color: var(--text-light); text-decoration: none; font-weight: 500;">
                Inscription
            </a>
        <?php endif; ?>
    </div>
</aside>
