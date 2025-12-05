/**
 * CovoiturageApp - Script Principal
 * Fonctionnalités globales et utilitaires
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log('CovoiturageApp loaded');
    
    // Initialiser les fonctionnalités
    initializeNavigation();
    initializeSmoothScroll();
});

/**
 * Initialiser la navigation
 */
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Supprimer la classe active de tous les liens
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Ajouter la classe active au lien cliqué
            this.classList.add('active');
        });
    });
}

/**
 * Scroll doux vers les sections
 */
function initializeSmoothScroll() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href === '#') {
                e.preventDefault();
                return;
            }
            
            const target = document.querySelector(href);
            
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Valider un email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Afficher un message d'alerte
 */
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    alert.textContent = message;
    
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alert, container.firstChild);
    
    // Enlever l'alerte après 5 secondes
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

/**
 * Formater une date
 */
function formatDate(date) {
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return new Date(date).toLocaleDateString('fr-FR', options);
}

/**
 * Convertir les minutes en heures et minutes
 */
function formatDuration(minutes) {
    if (!minutes || minutes < 60) {
        return minutes + ' min';
    }
    
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    let result = hours + ' h';
    if (mins > 0) {
        result += ' ' + mins + ' min';
    }
    
    return result;
}

/**
 * Générer un ID unique
 */
function generateId() {
    return '_' + Math.random().toString(36).substr(2, 9);
}

/**
 * Vérifier la connectivité internet
 */
function checkConnection() {
    return navigator.onLine;
}

// Afficher un avertissement si pas de connexion internet
window.addEventListener('offline', function() {
    showAlert('Vous êtes hors ligne. Vérifiez votre connexion Internet.', 'warning');
});

window.addEventListener('online', function() {
    showAlert('Connexion Internet rétablie.', 'success');
});
